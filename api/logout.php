<?php
// File: api/logout.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Include required files
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/SessionManager.php';

try {
    // Initialize database and session manager
    $database = new Database();
    $sessionManager = new SessionManager($database);
    
    // Get user info before logout for logging
    $user_email = User::getCurrentUserEmail();
    $user_id = User::getCurrentUserId();
    
    // Perform logout
    $sessionManager->logout();
    
    // Log logout
    if ($user_email) {
        error_log("User logged out: " . $user_email . " (ID: " . $user_id . ")");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Logout API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during logout'
    ]);
}
