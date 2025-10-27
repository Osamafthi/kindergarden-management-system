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
    
    // Check if user is logged in (can be teacher or admin)
    if (!User::isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit();
    }
    
    // Check if user is teacher or admin
    if (!User::isTeacher() && !User::isAdmin()) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher or Admin access required'
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
    
    // Validate required fields (student_id is no longer required)
    $required_fields = ['session_name', 'teacher_id', 'classroom_id'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: $field"
            ]);
            exit();
        }
    }
    
    // Get current user ID from session for security
    $current_user_id = User::getCurrentUserId();
    
    // Verify teacher ID based on user role
    if (User::isTeacher()) {
        // Teachers can only create sessions for themselves
        if ($input['teacher_id'] != $current_user_id) {
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized: Teachers can only create sessions for themselves'
            ]);
            exit();
        }
    } elseif (User::isAdmin()) {
        // Admins can create sessions for any teacher
        // No additional verification needed for admin users
    }
    
    // Create Sessions instance
    $sessions = new Sessions($database->connect());
    
    // Prepare session data (student_id will be handled by the class for all students)
    $session_data = [
        'session_name' => trim($input['session_name']),
        'teacher_id' => (int)$input['teacher_id'],
        'classroom_id' => (int)$input['classroom_id'],
        'date' => date('Y-m-d') // Current date
    ];
    
    // Add session
    $result = $sessions->add_Session($session_data);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'session_ids' => $result['session_ids'],
            'created_sessions' => $result['created_sessions'],
            'data' => $result['data']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Add session API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating the session'
    ]);
}
?>
