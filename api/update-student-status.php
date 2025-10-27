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
    if (!isset($data['student_id']) || !isset($data['status'])) {
        throw new Exception('Missing required fields: student_id and status');
    }
    
    $student_id = (int)$data['student_id'];
    $status = trim($data['status']);
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        throw new Exception('Invalid status. Must be either "active" or "inactive"');
    }
    
    // Create Student instance
    $student = new Student($db);
    
    // Update student status
    $result = $student->updateStudentStatus($student_id, $status);
    
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

