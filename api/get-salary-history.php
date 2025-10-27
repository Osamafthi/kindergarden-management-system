<?php
// File: api/get-salary-history.php

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

require_once __DIR__ . '/../includes/init.php';

// Response array
$response = ['success' => false, 'message' => ''];

// Check if the request is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Invalid request method. Only GET requests are allowed.';
    http_response_code(405);
    echo json_encode($response);
    exit;
}

// Check if database connection is available
if (!isset($db) || !$db) {
    $response['message'] = 'Database connection failed.';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

try {
    // Create Salary object
    $database = new Database();
    $db = $database->connect();
    $salary = new Salary($db);
    
    // Get parameters
    $teacher_id = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;
    
    // Validate required parameters
    if (!$teacher_id) {
        $response['message'] = 'teacher_id is required';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Get payment history
    $result = $salary->getPaymentHistory($teacher_id);
    
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
