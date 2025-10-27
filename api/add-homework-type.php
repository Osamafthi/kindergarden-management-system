<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include necessary files
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
    $required_fields = ['homework_type_name', 'description', 'max_grade', 'different_types'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Sanitize and validate data
    $homework_type_name = trim($data['homework_type_name']);
    $description = trim($data['description']);
    $max_grade = (int)$data['max_grade'];
    $different_types = trim($data['different_types']);
    
    // Additional validation
    if (strlen($homework_type_name) < 2) {
        throw new Exception('Homework type name must be at least 2 characters long');
    }
    
    if (strlen($description) < 10) {
        throw new Exception('Description must be at least 10 characters long');
    }
    
    if ($max_grade < 1 || $max_grade > 100) {
        throw new Exception('Maximum grade must be between 1 and 100');
    }
    
    if ($different_types !== 'quran' && $different_types !== 'modules') {
        throw new Exception('Different types must be either "quran" or "modules"');
    }
    
    // Check if homework type already exists
    $check_sql = "SELECT id FROM homework_types WHERE name = ?";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->bindParam(1, $homework_type_name, PDO::PARAM_STR);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        throw new Exception('Homework type with this name already exists');
    }
    
    // Insert new homework type
    $insert_sql = "INSERT INTO homework_types (name, description, max_grade, different_types, created_at) VALUES (?, ?, ?, ?, NOW())";
    $insert_stmt = $db->prepare($insert_sql);
    $insert_stmt->bindParam(1, $homework_type_name, PDO::PARAM_STR);
    $insert_stmt->bindParam(2, $description, PDO::PARAM_STR);
    $insert_stmt->bindParam(3, $max_grade, PDO::PARAM_INT);
    $insert_stmt->bindParam(4, $different_types, PDO::PARAM_STR);
    
    if ($insert_stmt->execute()) {
        $homework_type_id = $db->lastInsertId();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Homework type added successfully',
                'data' => [
                    'id' => $homework_type_id,
                    'homework_type_name' => $homework_type_name,
                    'description' => $description,
                    'max_grade' => $max_grade,
                    'different_types' => $different_types
                ]
        ]);
    } else {
        throw new Exception('Failed to add homework type to database');
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
