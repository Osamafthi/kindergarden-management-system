<?php
// File: api/reopen-attendance.php

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include required files
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/SessionManager.php';

// Response array
$response = ['success' => false, 'message' => ''];

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Only POST requests are allowed.';
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
    
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Check if data is valid JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Invalid JSON data: ' . json_last_error_msg();
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Get required attendance_record_id
    $attendance_record_id = isset($data['attendance_record_id']) ? (int)$data['attendance_record_id'] : null;
    
    if (!$attendance_record_id) {
        $response['message'] = 'attendance_record_id is required';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Reopen attendance
    $result = $attendance->reopen_attendance($attendance_record_id);
    
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
