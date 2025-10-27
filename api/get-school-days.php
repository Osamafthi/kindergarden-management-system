<?php
// File: api/get-school-days.php

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
    // Create Attendance object
    $database = new Database();
    $db = $database->connect();
    $attendance = new Attendance($db);
    
    // Get parameters
    $classroom_id = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : null;
    $term_id = isset($_GET['term_id']) ? (int)$_GET['term_id'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 1;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $specific_date = isset($_GET['date']) ? $_GET['date'] : null;
    
    // Validate required parameters
    if (!$classroom_id && !$term_id) {
        $response['message'] = 'Either classroom_id or term_id is required';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Get school days with pagination
    if ($classroom_id) {
        $result = $attendance->get_paginated_school_days($classroom_id, $limit, $offset, $specific_date);
    } else {
        $result = $attendance->get_school_days($term_id);
    }
    
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
