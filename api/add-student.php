<?php
// File: api/add-student.php

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../includes/init.php';

// Response array
$response = ['success' => false, 'message' => ''];

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method. Only POST requests are allowed.';
    http_response_code(405);
    echo json_encode($response);
    exit;
}

// Initialize data array
$data = [];
$photoPath = null;

// Check if this is a multipart form-data request (file upload)
if (!empty($_FILES) && isset($_FILES['photo'])) {
    // Handle file upload
    try {
        // Check for upload errors
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            ];
            
            $errorMsg = $uploadErrors[$_FILES['photo']['error']] ?? 'Unknown upload error';
            throw new Exception('File upload failed: ' . $errorMsg);
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['photo']['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF and WebP are allowed.');
        }
        
        // Validate file size (5MB max)
        $maxSize = 5 * 1024 * 1024;
        if ($_FILES['photo']['size'] > $maxSize) {
            throw new Exception('File size exceeds 5MB limit.');
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/kindergarden/assets/uploads/students/';    

        
        // Check if directory exists and is writable
        if (!file_exists($uploadDir)) {
            // Try to create the directory
            if (!mkdir($uploadDir, 0755, true)) {
                // If mkdir fails, try to determine why
                $parentDir = dirname($uploadDir);
                if (!is_writable(dirname($parentDir))) {
                    throw new Exception('Parent directory is not writable. Please check permissions for: ' . dirname($parentDir));
                } elseif (!is_writable($parentDir)) {
                    throw new Exception('Uploads directory is not writable. Please check permissions for: ' . $parentDir);
                } else {
                    throw new Exception('Failed to create directory. Please check server permissions.');
                }
            }
        }
        
        // Double check that the directory is writable
        if (!is_writable($uploadDir)) {
            // Try to fix permissions
            if (!chmod($uploadDir, 0755)) {
                throw new Exception('Upload directory exists but is not writable. Please check permissions for: ' . $uploadDir);
            }
        }
        
        // Generate unique filename
        $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        // Debug information
        error_log("Attempting to move uploaded file from: " . $_FILES['photo']['tmp_name']);
        error_log("Attempting to move uploaded file to: " . $destination);
        error_log("Is destination directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No'));
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
            $photoPath = 'assets/uploads/students/' . $filename;
            $data['photo'] = $photoPath;
            error_log("File successfully moved to: " . $destination);
        } else {
            // Get detailed error information
            $error = error_get_last();
            $errorMessage = $error['message'] ?? 'Unknown error';
            
            // Check common issues
            if (!is_writable($uploadDir)) {
                $errorMessage = 'Destination directory is not writable: ' . $uploadDir;
            } elseif (!file_exists($_FILES['photo']['tmp_name'])) {
                $errorMessage = 'Temporary file does not exist: ' . $_FILES['photo']['tmp_name'];
            }
            
            error_log("Failed to move uploaded file: " . $errorMessage);
            throw new Exception('Failed to save uploaded file: ' . $errorMessage);
        }
    } catch (Exception $e) {
        $response['message'] = 'Photo upload error: ' . $e->getMessage();
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Get other form data
    $data['firstName'] = $_POST['firstName'] ?? '';
    $data['lastName'] = $_POST['lastName'] ?? '';
    $data['dateOfBirth'] = $_POST['dateOfBirth'] ?? '';
    $data['gender'] = $_POST['gender'] ?? '';
    $data['enrollmentDate'] = $_POST['enrollmentDate'] ?? '';
    $data['studentLevel'] = $_POST['studentLevel'] ?? '';
} else {
    // Handle JSON input (for requests without file upload)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Check if data is valid JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        $response['message'] = 'Invalid JSON data: ' . json_last_error_msg();
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
}

// Check if database connection is available
if (!isset($db) || !$db) {
    // Clean up uploaded file if database connection fails
    if ($photoPath && file_exists(__DIR__ . '/../' . $photoPath)) {
        unlink(__DIR__ . '/../' . $photoPath);
    }
    
    $response['message'] = 'Database connection failed.';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

try {
    // Create Student object
    $student = new Student($db);
    
    // Add student using the class method
    $result = $student->add_student($data);
    
    // If student creation failed, clean up the uploaded file
    if (!$result['success'] && $photoPath && file_exists(__DIR__ . '/../' . $photoPath)) {
        unlink(__DIR__ . '/../' . $photoPath);
    }
    
    // Set appropriate HTTP status code
    if ($result['success']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }
    
    // Return the result
    echo json_encode($result);
    
} catch (Exception $e) {
    // Clean up uploaded file if an exception occurs
    if ($photoPath && file_exists(__DIR__ . '/../' . $photoPath)) {
        unlink(__DIR__ . '/../' . $photoPath);
    }
    
    // Log the error
    error_log("API Error: " . $e->getMessage());
    
    // Return error response
    $response['message'] = 'Server error: ' . $e->getMessage();
    http_response_code(500);
    echo json_encode($response);
}