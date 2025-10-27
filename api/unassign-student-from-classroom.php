<?php
// File: api/unassign-student-from-classroom.php
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
require_once __DIR__ . '/../includes/SessionManager.php';

try {
    // Initialize database and session manager
    $database = new Database();
    $sessionManager = new SessionManager($database);
    
    // Check if user is logged in and is an admin
    if (!User::isLoggedIn() || !User::isAdmin()) {
        echo json_encode([
            'success' => false,
            'message' => 'Admin access required'
        ]);
        exit();
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit();
    }
    
    // Validate required fields
    if (!isset($input['student_id'])) {
        echo json_encode(['success' => false, 'message' => 'Student ID is required']);
        exit();
    }
    
    $student_id = (int)$input['student_id'];
    
    // Validate ID is positive integer
    if ($student_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Student ID']);
        exit();
    }
    
    // Initialize Student instance
    $student = new Student($database->connect());
    
    // Unassign student from classroom
    $result = $student->unassignStudentFromClassroom($student_id);
    
    if ($result['success']) {
        // Log successful unassignment
        error_log("Student $student_id unassigned from classroom {$result['previous_classroom_name']} successfully");
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'student_id' => $result['student_id'],
            'previous_classroom_id' => $result['previous_classroom_id'],
            'previous_classroom_name' => $result['previous_classroom_name']
        ]);
    } else {
        // Log failed unassignment
        error_log("Failed to unassign student $student_id from classroom: " . $result['message']);
        
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Unassign student from classroom API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
?>
