<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include necessary files
require_once '../includes/autoload.php';
require_once '../includes/SessionManager.php';
require_once '../config.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Debug: Log received data
    error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
    error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    error_log("REQUEST data: " . print_r($_REQUEST, true));
    
    // Initialize database and session manager
    $database = new Database();
    $sessionManager = new SessionManager($database);
    
    // Check if user is logged in and is a teacher or admin
    if (!User::isLoggedIn() || (!User::isTeacher() && !User::isAdmin())) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher or Admin access required'
        ]);
        exit();
    }
    
    // Check if POST data is empty (might happen with some server configurations)
    if (empty($_POST) && !empty($_FILES)) {
        error_log("Warning: POST data is empty but FILES is not. This might be a server configuration issue.");
        error_log("Attempting to parse input stream...");
        // Try to get raw input
        $input = file_get_contents('php://input');
        error_log("Raw input length: " . strlen($input));
    }
    
    // Validate required fields (excluding file which is checked separately)
    $required_fields = ['session_homework_id', 'homework_type_id', 'lesson_title', 'classroom_id'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || $_POST[$field] === '' || $_POST[$field] === null) {
            error_log("Missing field: $field");
            error_log("Available POST fields: " . implode(', ', array_keys($_POST)));
            echo json_encode([
                'success' => false,
                'message' => "Missing required field: $field. Available fields: " . implode(', ', array_keys($_POST))
            ]);
            exit();
        }
    }
    
    // Validate lesson title length
    if (strlen($_POST['lesson_title']) < 2) {
        echo json_encode([
            'success' => false,
            'message' => 'Lesson title must be at least 2 characters long'
        ]);
        exit();
    }
    
    if (strlen($_POST['lesson_title']) > 255) {
        echo json_encode([
            'success' => false,
            'message' => 'Lesson title must not exceed 255 characters'
        ]);
        exit();
    }
    
    // Handle file upload
    if (!isset($_FILES['file'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No file uploaded. Please select a file.'
        ]);
        exit();
    }
    
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File too large (exceeds upload_max_filesize of 2M). Contact administrator to increase limits.',
            UPLOAD_ERR_FORM_SIZE => 'File too large (exceeds MAX_FILE_SIZE)',
            UPLOAD_ERR_PARTIAL => 'File upload was interrupted',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $error_message = isset($error_messages[$_FILES['file']['error']]) 
            ? $error_messages[$_FILES['file']['error']] 
            : 'Unknown upload error';
            
        error_log("File upload error code: " . $_FILES['file']['error']);
        error_log("File size: " . $_FILES['file']['size'] . " bytes");
            
        echo json_encode([
            'success' => false,
            'message' => 'File upload failed: ' . $error_message . ' (File size: ' . round($_FILES['file']['size'] / 1048576, 2) . ' MB)'
        ]);
        exit();
    }
    
    $file = $_FILES['file'];
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Only PDF, JPG, JPEG, and PNG files are allowed.'
        ]);
        exit();
    }
    
    // Check file size (90MB max)
    $max_file_size = 90 * 1024 * 1024; // 90MB in bytes
    if ($file['size'] > $max_file_size) {
        echo json_encode([
            'success' => false,
            'message' => 'File size exceeds the maximum allowed size of 90MB. Current size: ' . round($file['size'] / 1048576, 2) . ' MB'
        ]);
        exit();
    }
    
    // Generate unique filename
// Get the document root and build path from there
  $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/kindergarden/assets/uploads/modules/';    
    // Check if directory exists, create if not
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create upload directory'
            ]);
            exit();
        }
    }
    
    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        echo json_encode([
            'success' => false,
            'message' => 'Upload directory is not writable'
        ]);
        exit();
    }
    
    $filename = uniqid() . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $filename;
    
    // Test if we can write to the directory
    $test_file = $upload_dir . 'test_write.txt';
    if (file_put_contents($test_file, 'test') === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot write to upload directory. Directory permissions issue.'
        ]);
        exit();
    }
    unlink($test_file); // Clean up test file
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        error_log("Failed to move uploaded file from " . $file['tmp_name'] . " to " . $file_path);
        error_log("File info: " . print_r($file, true));
        error_log("Upload directory: " . $upload_dir);
        error_log("Directory exists: " . (is_dir($upload_dir) ? 'yes' : 'no'));
        error_log("Directory writable: " . (is_writable($upload_dir) ? 'yes' : 'no'));
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save uploaded file. Check server logs for details.'
        ]);
        exit();
    }
    
    error_log("File uploaded successfully: " . $file_path);
    
    // Create Homework instance
    $homework = new Homework($database->connect());
    
    // Prepare homework module data
    $homework_data = [
        'session_homework_id' => (int)$_POST['session_homework_id'],
        'homework_type_id' => (int)$_POST['homework_type_id'],
        'lesson_title' => trim($_POST['lesson_title']),
        'photo' => 'assets/uploads/modules/' . $filename, // Store relative path
        'classroom_id' => (int)$_POST['classroom_id']
    ];
    
    // Add homework module
    $result = $homework->addHomeworkModule($homework_data);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'module_id' => $result['module_id'],
            'data' => $result['data']
        ]);
    } else {
        // If database insert failed, delete the uploaded file
        unlink($file_path);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Add homework module API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while adding homework module data'
    ]);
}
?>
