<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/init.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    if (!isset($data['teacher_id']) || !isset($data['is_active'])) {
        throw new Exception('Missing required fields: teacher_id and is_active');
    }
    
    $teacher_id = (int)$data['teacher_id'];
    $is_active = (int)$data['is_active']; // 1 for active, 0 for inactive
    
    // Validate is_active
    if (!in_array($is_active, [0, 1])) {
        throw new Exception('Invalid is_active value. Must be either 0 or 1');
    }
    
    // Create Teacher instance
    $teacher = new Teacher($db);
    
    // Update teacher status
    $result = $teacher->updateTeacherStatus($teacher_id, $is_active);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        throw new Exception($result['message']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
}
?>

