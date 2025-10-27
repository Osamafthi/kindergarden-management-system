<?php
// File: api/session-check.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include required files
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/SessionManager.php';

try {
    // Initialize database and session manager
    $database = new Database();
    $sessionManager = new SessionManager($database);
    
    // Check if user is logged in
    if (User::isLoggedIn()) {
        // Update last activity
        $sessionManager->updateLastActivity();
        
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'session_info' => $sessionManager->getSessionInfo(),
            'is_admin' => User::isAdmin(),
            'is_teacher' => User::isTeacher()
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'logged_in' => false,
            'message' => 'User not logged in'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Session check API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred checking session'
    ]);
}
