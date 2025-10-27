<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters to ensure proper sharing
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

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
    // Initialize database
    $database = new Database();
    
    // Check if user is logged in (can be teacher or admin)
    if (!User::isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit();
    }
    
    // Check if user is admin (only admins can view products)
    if (!User::isAdmin()) {
        echo json_encode([
            'success' => false,
            'message' => 'Admin access required'
        ]);
        exit();
    }
    
    // Create Inventory instance
    $inventory = new Inventory($database->connect());
    
    // Get products
    $result = $inventory->get_products();
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'products' => $result['data'],
            'count' => $result['count']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get products API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching products'
    ]);
}
?>
