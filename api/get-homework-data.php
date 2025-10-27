<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include necessary files
require_once '../includes/autoload.php';
require_once '../includes/SessionManager.php';

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    
    // Get parameters from query string
    $session_name = isset($_GET['session_name']) ? $_GET['session_name'] : null;
    $session_date = isset($_GET['session_date']) ? $_GET['session_date'] : null;
    $classroom_id = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : null;
    
    // Validate required parameters
    if (!$session_name || !$session_date || !$classroom_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters: session_name, session_date, classroom_id'
        ]);
        exit();
    }
    
    // Create Homework instance
    $homework = new Homework($database->connect());
    
    // Get homework data
    $result = $homework->getHomeworkData($session_name, $session_date, $classroom_id);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'homework_data' => $result['homework_data'],
            'count' => $result['count'],
            'session_name' => $session_name,
            'session_date' => $session_date,
            'classroom_id' => $classroom_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get homework data API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching homework data'
    ]);
}
?>
