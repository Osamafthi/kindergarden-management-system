<?php
// File: api/mark-salary-paid.php

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

require_once __DIR__ . '/../includes/init.php';

// Response array
$response = ['success' => false, 'message' => ''];

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Only POST requests are allowed.';
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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Get parameters
    $teacher_id = isset($input['teacher_id']) ? (int)$input['teacher_id'] : null;
    $month = isset($input['month']) ? $input['month'] : null;
    $amount = isset($input['amount']) ? $input['amount'] : null;
    
    // Validate required parameters
    if (!$teacher_id) {
        $response['message'] = 'teacher_id is required';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    if (!$month) {
        $response['message'] = 'month is required';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Validate month format
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $response['message'] = 'Invalid month format. Use YYYY-MM';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Validate amount if provided
    if ($amount !== null && (!is_numeric($amount) || $amount < 0)) {
        $response['message'] = 'Invalid amount. Must be a positive number.';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Create Salary object
    $database = new Database();
    $db = $database->connect();
    $salary = new Salary($db);
    
    // Mark salary as paid
    $result = $salary->markAsPaid($teacher_id, $month, $amount);
    
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
