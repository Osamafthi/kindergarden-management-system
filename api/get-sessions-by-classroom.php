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
    
    // Get classroom_id from query parameters
    $classroom_id = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : null;
    
    if (!$classroom_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Classroom ID is required'
        ]);
        exit();
    }
    
    // Get optional pagination parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Create Sessions instance
    $sessions = new Sessions($database->connect());
    
    // Get sessions for the specified classroom
    $result = $sessions->getSessionsByClassroom($classroom_id, $limit, $offset);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'sessions' => $result['sessions'],
            'count' => $result['count'],
            'classroom_id' => $classroom_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get sessions by classroom API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching sessions'
    ]);
}
?>
