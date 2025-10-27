<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/autoload.php';
require_once '../includes/SessionManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $database = new Database();
    $sessionManager = new SessionManager($database);
    
    if (!User::isLoggedIn() || !User::isTeacher()) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher access required'
        ]);
        exit();
    }
    
    // Get parameters
    $session_name = $_GET['session_name'] ?? null;
    $session_date = $_GET['session_date'] ?? null;
    $classroom_id = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : null;
    $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
    
    if (!$session_name || !$session_date || !$classroom_id || !$student_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters'
        ]);
        exit();
    }
    
    // Get pagination parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $homework = new Homework($database->connect());
    
    // Get module homework data for the session
    $result = $homework->getModuleHomeworkData($session_name, $session_date, $classroom_id, $student_id, $limit, $offset);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'homework_data' => $result['homework_data'],
            'count' => $result['count']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get homework module data API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching homework module data'
    ]);
}
?>
