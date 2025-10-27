<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include necessary files
require_once '../includes/autoload.php';
require_once '../includes/SessionManager.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Initialize database and session manager
    $database = new Database();
    $sessionManager = new SessionManager($database);
    
    // Check if user is logged in and is a teacher
    if (!User::isLoggedIn() || !User::isTeacher()) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher access required'
        ]);
        exit();
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input'
        ]);
        exit();
    }
    
    // Validate required fields
    $required_fields = ['session_id', 'student_id', 'grades'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: $field"
            ]);
            exit();
        }
    }
    
    // Validate grades array
    if (!is_array($input['grades']) || empty($input['grades'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Grades data must be a non-empty array'
        ]);
        exit();
    }
    
    // Get teacher ID from session
    $teacher_id = User::getCurrentUserId();
    
    if (!$teacher_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher ID not found in session'
        ]);
        exit();
    }
    
    // Prepare data for Homework class
    $grades_data = [
        'session_id' => (int)$input['session_id'],
        'teacher_id' => (int)$teacher_id,
        'student_id' => (int)$input['student_id'],
        'grades' => $input['grades']
    ];
    
    // Validate each grade item
    foreach ($grades_data['grades'] as $index => $grade_item) {
        if (!isset($grade_item['homework_type_id']) || !isset($grade_item['session_module_id'])) {
            echo json_encode([
                'success' => false,
                'message' => "Missing homework_type_id or session_module_id in grade item at index $index"
            ]);
            exit();
        }
        
        // Convert to integers
        $grades_data['grades'][$index]['homework_type_id'] = (int)$grade_item['homework_type_id'];
        $grades_data['grades'][$index]['session_module_id'] = (int)$grade_item['session_module_id'];
        
        // Handle grade value (can be null if not provided)
        if (isset($grade_item['grade']) && $grade_item['grade'] !== '') {
            $grades_data['grades'][$index]['grade'] = (int)$grade_item['grade'];
        } else {
            $grades_data['grades'][$index]['grade'] = null;
        }
    }
    
    // Create Homework instance
    $homework = new Homework($database->connect());
    
    // Add module grades to database
    $result = $homework->addGradeHomeworkModule($grades_data);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'inserted_grades' => $result['inserted_grades'],
            'count' => $result['count']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message'],
            'errors' => isset($result['errors']) ? $result['errors'] : []
        ]);
    }
    
} catch (Exception $e) {
    error_log("Add grade homework module API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while saving module grades'
    ]);
}
?>
