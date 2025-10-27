<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include necessary files
require_once '../includes/init.php';

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get query parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $is_active = isset($_GET['is_active']) ? (int)$_GET['is_active'] : 1; // Default to active teachers
    
    // Create Teacher instance
    $teacher = new Teacher($db);
    
    // Fetch teachers based on search or get all
    if (!empty($search)) {
        $result = $teacher->searchTeachers($search, $limit, $offset, $is_active);
    } else {
        $result = $teacher->getAllTeachers($limit, $offset, $is_active);
    }
    
    // Get total count for pagination based on is_active status
    $count_result = $teacher->getAllTeachers(null, 0, $is_active);
    $total_count = $count_result['success'] ? count($count_result['teachers']) : 0;
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'teachers' => $result['teachers'],
            'count' => $result['count'],
            'total_count' => $total_count,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => $limit ? ($offset + $limit) < $total_count : false
            ]
        ]);
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
