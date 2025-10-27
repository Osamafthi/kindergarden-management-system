<?php
// File: api/assign-student-to-classroom.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Include required files
require_once __DIR__ . '/../includes/autoload.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit();
    }
    
    // Validate required fields
    if (!isset($input['student_id']) || !isset($input['classroom_id'])) {
        echo json_encode(['success' => false, 'message' => 'Student ID and Classroom ID are required']);
        exit();
    }
    
    $student_id = (int)$input['student_id'];
    $classroom_id = (int)$input['classroom_id'];
    
    // Validate IDs are positive integers
    if ($student_id <= 0 || $classroom_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Student ID or Classroom ID']);
        exit();
    }
    
    // Initialize database and Student instance
    $database = new Database();
    $student = new Student($database->connect());
    
    // Assign student to classroom
    $result = $student->assignStudentToClassroom($student_id, $classroom_id);
    
    if ($result['success']) {
        // Log successful assignment
        error_log("Student $student_id assigned to classroom $classroom_id successfully");
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'student_id' => $result['student_id'],
            'classroom_id' => $result['classroom_id'],
            'classroom_name' => $result['classroom_name']
        ]);
    } else {
        // Log failed assignment
        error_log("Failed to assign student $student_id to classroom $classroom_id: " . $result['message']);
        
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Assign student to classroom API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
