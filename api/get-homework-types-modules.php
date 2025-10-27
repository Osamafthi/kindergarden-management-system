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
    
    // Check if user is logged in and is a teacher or admin
    if (!User::isLoggedIn() || (!User::isTeacher() && !User::isAdmin())) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher or Admin access required'
        ]);
        exit();
    }
    
    // Get optional pagination parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Create Homework instance
    $homework = new Homework($database->connect());
    
    // Get homework types (filtered by modules type)
    $result = $homework->getHomeworkTypes($limit, $offset, 'modules');
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'homework_types' => $result['homework_types'],
            'count' => $result['count']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get homework types modules API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching homework types'
    ]);
}
?>
