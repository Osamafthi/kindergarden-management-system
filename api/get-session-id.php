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
    
    // Create Sessions instance
    $sessions = new Sessions($database->connect());
    
    // Get session ID by name, date, and classroom
    $sql = "SELECT id FROM sessions 
            WHERE session_name = :session_name 
            AND date = :session_date 
            AND classroom_id = :classroom_id 
            LIMIT 1";
    
    $stmt = $database->connect()->prepare($sql);
    $stmt->bindParam(':session_name', $session_name, PDO::PARAM_STR);
    $stmt->bindParam(':session_date', $session_date, PDO::PARAM_STR);
    $stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($session) {
        echo json_encode([
            'success' => true,
            'session_id' => (int)$session['id'],
            'session_name' => $session_name,
            'session_date' => $session_date,
            'classroom_id' => $classroom_id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Session not found'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get session ID API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching session ID'
    ]);
}
?>
