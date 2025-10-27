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
    
   
    
    // Get required parameters
    $student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
    $period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
    
    // Validate student_id
    if (!$student_id || $student_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Valid student ID is required'
        ]);
        exit();
    }
    
    // Validate period
    if (!in_array($period, ['weekly', 'monthly'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Period must be either weekly or monthly'
        ]);
        exit();
    }
    
    // Create Reports instance
    $reports = new Reports($database->connect());
    
    // Get student report
    $result = $reports->getStudentReport($student_id, $period);
    
    // Return the result
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Get student report API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while generating the report'
    ]);
}
?>
