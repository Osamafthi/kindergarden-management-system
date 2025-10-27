<?php
// File: api/get-attendance.php

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include required files
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/SessionManager.php';

// Response array
$response = ['success' => false, 'message' => ''];

// Check if the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Invalid request method. Only GET requests are allowed.';
    http_response_code(405);
    echo json_encode($response);
    exit;
}

// Initialize database and session manager
$database = new Database();
$sessionManager = new SessionManager($database);

// Check if user is logged in and is a teacher
if (!User::isLoggedIn() || !User::isTeacher()) {
    $response['message'] = 'Teacher authentication required';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

try {
    // Create Attendance object
    $attendance = new Attendance($database->connect());
    
    // Get required parameters
    $classroom_id = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : null;
    $school_day_id = isset($_GET['school_day_id']) ? (int)$_GET['school_day_id'] : null;
    
    if (!$classroom_id || !$school_day_id) {
        $response['message'] = 'Both classroom_id and school_day_id are required';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Get attendance record
    $result = $attendance->get_attendance_record($classroom_id, $school_day_id);
    
    // Return the result
    echo json_encode($result);
    
} catch (Exception $e) {
    // Log the error
    error_log("API Error: " . $e->getMessage());
    
    // Return error response
    $response['message'] = 'Server error: ' . $e->getMessage();
    http_response_code(500);
    echo json_encode($response);
}
?>
