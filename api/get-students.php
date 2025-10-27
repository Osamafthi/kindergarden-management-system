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
    $status = isset($_GET['status']) ? $_GET['status'] : 'active'; // Default to active students only
    
    // Create Student instance
    $student = new Student($db);
    
    // Fetch students based on search or get all
    if (!empty($search)) {
        $result = $student->searchStudents($search, $limit, $offset, $status);
    } else {
        $result = $student->getAllStudents($limit, $offset, $status);
    }
    
    // Get total count for pagination (based on status)
    $count_result = $student->getAllStudents(null, 0, $status);
    $total_count = $count_result['success'] ? count($count_result['students']) : 0;
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'students' => $result['students'],
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
