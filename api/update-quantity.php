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
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include necessary files
require_once '../includes/autoload.php';
require_once '../includes/SessionManager.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    
    // Check if user is admin (only admins can update quantities)
    if (!User::isAdmin()) {
        echo json_encode([
            'success' => false,
            'message' => 'Admin access required'
        ]);
        exit();
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input'
        ]);
        exit();
    }
    
    // Validate required fields
    $required_fields = ['product_id', 'quantity', 'movement_type'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: $field"
            ]);
            exit();
        }
    }
    
    // Validate movement_type
    if (!in_array($input['movement_type'], ['IN', 'OUT'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid movement type. Must be IN or OUT'
        ]);
        exit();
    }
    
    // Create Inventory instance
    $inventory = new Inventory($database->connect());
    
    // Check if this is a reset operation (Edit Product)
    if (isset($input['reset_original']) && $input['reset_original'] === true) {
        $result = $inventory->reset_original_quantity(
            (int)$input['product_id'],
            (int)$input['quantity']
        );
    } else {
        $result = $inventory->update_quantity(
            (int)$input['product_id'],
            (int)$input['quantity'],
            $input['movement_type']
        );
    }
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'new_quantity' => $result['new_quantity']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Update quantity API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating quantity'
    ]);
}
?>
