<?php
// File: api/save-attendance.php

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include required files
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/SessionManager.php';

// Response array
$response = ['success' => false, 'message' => ''];

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Only POST requests are allowed.';
    http_response_code(405);
    echo json_encode($response);
    exit;
}

// Initialize database and session manager
$database = new Database();
$sessionManager = new SessionManager($database);

// Check if user is logged in and is a teacher
if (!User::isLoggedIn() || !User::isTeacher()) {
    $response['message'] = 'Teacher authentication required';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

try {
    // Create Attendance object and get database connection
    $db = $database->connect();
    $attendance = new Attendance($db);
    
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Check if data is valid JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Invalid JSON data: ' . json_last_error_msg();
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Validate required fields
    $required_fields = ['classroom_id', 'school_day_id', 'academic_year_id', 'term_id', 'students'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            $response['message'] = "Missing required field: $field";
            http_response_code(400);
            echo json_encode($response);
            exit;
        }
    }
    
    // Validate students array
    if (!is_array($data['students']) || empty($data['students'])) {
        $response['message'] = 'Students array is required and must not be empty';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Get teacher ID from authenticated user
    $teacher_id = User::getCurrentUserId();
    
    $attendance_record_id = null;
    
    // Check if we're updating an existing record (provided by frontend)
    if (isset($data['attendance_record_id']) && !empty($data['attendance_record_id'])) {
        $attendance_record_id = $data['attendance_record_id'];
        error_log("Using provided attendance_record_id: " . $attendance_record_id);
        error_log("Classroom ID: " . $data['classroom_id']);
        error_log("School Day ID: " . $data['school_day_id']);
        
        // Verify the record exists by ID directly (not by classroom/school_day lookup)
        $verify_sql = "SELECT ar.*, sd.date as school_date 
                       FROM attendance_records ar 
                       LEFT JOIN school_days sd ON ar.school_day_id = sd.id
                       WHERE ar.id = :attendance_record_id";
        $verify_stmt = $db->prepare($verify_sql);
        $verify_stmt->bindParam(':attendance_record_id', $attendance_record_id, PDO::PARAM_INT);
        $verify_stmt->execute();
        $record = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            error_log("No attendance record found with ID: " . $attendance_record_id);
            throw new Exception("Attendance record not found with ID: " . $attendance_record_id);
        }
        
        // Verify it belongs to the correct classroom and school day
        if ($record['classroom_id'] != $data['classroom_id'] || $record['school_day_id'] != $data['school_day_id']) {
            error_log("Record mismatch - Record classroom: " . $record['classroom_id'] . ", Provided: " . $data['classroom_id']);
            error_log("Record school_day: " . $record['school_day_id'] . ", Provided: " . $data['school_day_id']);
            throw new Exception("Attendance record does not match provided classroom/school day");
        }
        
        error_log("Record verified successfully: " . json_encode($record));
        
        // Reopen if closed
        if ($record['status'] === 'closed') {
            $reopen_result = $attendance->reopen_attendance($attendance_record_id);
            if (!$reopen_result['success']) {
                throw new Exception($reopen_result['message']);
            }
        }
    } else {
        // Check if attendance record already exists
        $existing_record = $attendance->get_attendance_record($data['classroom_id'], $data['school_day_id']);
        
        if ($existing_record['success']) {
            // Update existing record
            $attendance_record_id = $existing_record['attendance_record']['id'];
            
            // Reopen if closed
            if ($existing_record['attendance_record']['status'] === 'closed') {
                $reopen_result = $attendance->reopen_attendance($attendance_record_id);
                if (!$reopen_result['success']) {
                    throw new Exception($reopen_result['message']);
                }
            }
        } else {
        // Create new attendance record
        $record_data = [
            'classroom_id' => $data['classroom_id'],
            'teacher_id' => $teacher_id,
            'school_day_id' => $data['school_day_id'],
            'academic_year_id' => $data['academic_year_id'],
            'term_id' => $data['term_id']
        ];
        
        $record_result = $attendance->create_attendance_record($record_data);
        
        if (!$record_result['success']) {
            // If it's a duplicate entry error, try to get the existing record
            if (strpos($record_result['message'], 'Duplicate entry') !== false) {
                $existing_record = $attendance->get_attendance_record($data['classroom_id'], $data['school_day_id']);
                if ($existing_record['success']) {
                    $attendance_record_id = $existing_record['attendance_record']['id'];
                } else {
                    throw new Exception("Failed to create attendance record and could not find existing record: " . $record_result['message']);
                }
            } else {
                throw new Exception($record_result['message']);
            }
        } else {
            $attendance_record_id = $record_result['attendance_record_id'];
        }
    }
    }
    
    // Save student attendance
    $students_result = $attendance->save_student_attendance($attendance_record_id, $data['students']);
    if (!$students_result['success']) {
        throw new Exception($students_result['message']);
    }
    
    // Close the attendance record
    $close_sql = "UPDATE attendance_records SET status = 'closed' WHERE id = :attendance_record_id";
    $close_stmt = $db->prepare($close_sql);
    $close_stmt->bindParam(':attendance_record_id', $attendance_record_id, PDO::PARAM_INT);
    $close_stmt->execute();
    
    // Return success response
    $response = [
        'success' => true,
        'message' => 'Attendance saved successfully',
        'attendance_record_id' => $attendance_record_id,
        'student_count' => count($data['students'])
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log the error
    error_log("API Error: " . $e->getMessage());
    
    // Return error response
    $response['message'] = 'Server error: ' . $e->getMessage();
    http_response_code(500);
    echo json_encode($response);
}
?>
