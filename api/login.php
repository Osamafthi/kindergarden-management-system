<?php
// File: api/login.php
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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit();
    }
    
    // Validate required fields
    if (!isset($input['email']) || !isset($input['password'])) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit();
    }
    
    $email = trim($input['email']);
    $password = $input['password'];
    $remember = isset($input['remember']) ? (bool)$input['remember'] : false;
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit();
    }
    
    // Validate password length
    if (strlen($password) < 1) {
        echo json_encode(['success' => false, 'message' => 'Password is required']);
        exit();
    }
    
    // Initialize database and session manager
    $database = new Database();
    $sessionManager = new SessionManager($database);
    
    // Create User instance
    $user = new User($database->connect());
    
    // Attempt login
    $result = $user->login($email, $password, $remember);
    
    if ($result['success']) {
        // Update last activity
        $sessionManager->updateLastActivity();
        
        // Regenerate session ID for security
        $sessionManager->regenerateSessionId();
        
        // Log successful login
        error_log("Successful login for user: " . $email . " (ID: " . $result['user_id'] . ")");
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'user_id' => $result['user_id'],
            'user_email' => $result['user_email'],
            'user_name' => $result['user_name'],
            'user_role' => $result['user_role'],
            'teacher_id' => $result['teacher_id'],
            'session_info' => $sessionManager->getSessionInfo()
        ]);
    } else {
        // Log failed login attempt
        error_log("Failed login attempt for email: " . $email . " - " . $result['message']);
        
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Login API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
