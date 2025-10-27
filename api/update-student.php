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
    // Validate required fields (excluding enrollmentDate)
    $required_fields = ['studentId', 'firstName', 'lastName', 'dateOfBirth', 'gender', 'studentLevel'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Field '{$field}' is required");
        }
    }
    
    // Create Student instance
    $student = new Student($db);
    
    // Prepare student data array
    $student_data = [
        'studentId' => $_POST['studentId'],
        'firstName' => $_POST['firstName'],
        'lastName' => $_POST['lastName'],
        'dateOfBirth' => $_POST['dateOfBirth'],
        'gender' => $_POST['gender'],
        'studentLevel' => $_POST['studentLevel'],
        'photo' => '' // Default empty, will be updated if file is uploaded
    ];
    
    // Handle photo upload if provided
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/uploads/students/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $filename;
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF files are allowed.');
        }
        
        // Validate file size (5MB limit)
        if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size must be less than 5MB.');
        }
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
            $student_data['photo'] = 'assets/uploads/students/' . $filename;
        } else {
            throw new Exception('Failed to upload photo.');
        }
    }
    
    // Set student data using the helper function
    $student->setStudentData($student_data);
    
    // Update the student
    $result = $student->updateStudent();
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Student updated successfully',
            'student_id' => $result['student_id']
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
