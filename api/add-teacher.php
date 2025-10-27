<?php
// File: api/add-teacher.php

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


require_once  __DIR__ .'/../includes/init.php';

// Response array
$response = ['success' => false, 'message' => ''];

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Only POST requests are allowed.';
    http_response_code(405);
    echo json_encode($response);
    exit;
}

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

// Check if database connection is available
if (!isset($db) || !$db) {
    $response['message'] = 'Database connection failed.';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

try {
    // Create Teacher object
    $database = new Database();
    $db = $database->connect();
    $teacher = new Teacher($db);
    
    // Add teacher using the class method
    $result = $teacher->addTeacher($data);
    
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
