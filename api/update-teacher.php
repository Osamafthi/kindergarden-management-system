<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include necessary files
require_once '../includes/init.php';

// Check if request method is PUT or POST
if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) {
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
    if (!isset($data['teacher_id']) || empty($data['teacher_id'])) {
        throw new Exception('Teacher ID is required');
    }
    
    // Validate teacher_id is numeric
    $teacher_id = (int)$data['teacher_id'];
    if ($teacher_id <= 0) {
        throw new Exception('Invalid teacher ID');
    }
    
    // Remove teacher_id from data array as it's not part of the update fields
    unset($data['teacher_id']);
    
    // Validate that we have at least one field to update
    if (empty($data)) {
        throw new Exception('No fields provided for update');
    }
    
    // Sanitize and validate individual fields if they exist
    $updateData = [];
    
    if (isset($data['full_name'])) {
        $updateData['full_name'] = trim($data['full_name']);
        if (empty($updateData['full_name'])) {
            throw new Exception('Full name cannot be empty');
        }
        if (strlen($updateData['full_name']) < 2) {
            throw new Exception('Full name must be at least 2 characters long');
        }
    }
    
    if (isset($data['phone_number'])) {
        $updateData['phone_number'] = trim($data['phone_number']);
        if (empty($updateData['phone_number'])) {
            throw new Exception('Phone number cannot be empty');
        }
        // Remove non-numeric characters for validation
        $phone_digits = preg_replace('/[^0-9]/', '', $updateData['phone_number']);
        if (strlen($phone_digits) < 10) {
            throw new Exception('Phone number must be at least 10 digits long');
        }
    }
    
    if (isset($data['email'])) {
        $updateData['email'] = trim($data['email']);
        if (empty($updateData['email'])) {
            throw new Exception('Email cannot be empty');
        }
        if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
    }
    
    if (isset($data['gender'])) {
        $updateData['gender'] = trim($data['gender']);
        if (empty($updateData['gender'])) {
            throw new Exception('Gender cannot be empty');
        }
        $valid_genders = ['male', 'female', 'other'];
        if (!in_array(strtolower($updateData['gender']), $valid_genders)) {
            throw new Exception('Invalid gender. Must be male, female, or other');
        }
        $updateData['gender'] = strtolower($updateData['gender']);
    }
    
    if (isset($data['hourly_rate'])) {
        $updateData['hourly_rate'] = $data['hourly_rate'];
        if (!is_numeric($updateData['hourly_rate']) || $updateData['hourly_rate'] < 0) {
            throw new Exception('Hourly rate must be a positive number');
        }
        $updateData['hourly_rate'] = (float)$updateData['hourly_rate'];
    }
    
    if (isset($data['monthly_salary'])) {
        $updateData['monthly_salary'] = $data['monthly_salary'];
        if (!is_numeric($updateData['monthly_salary']) || $updateData['monthly_salary'] < 0) {
            throw new Exception('Monthly salary must be a positive number');
        }
        $updateData['monthly_salary'] = (float)$updateData['monthly_salary'];
    }
    
    if (isset($data['password'])) {
        $updateData['password'] = $data['password'];
        if (empty($updateData['password'])) {
            throw new Exception('Password cannot be empty');
        }
        if (strlen($updateData['password']) < 6) {
            throw new Exception('Password must be at least 6 characters long');
        }
        // Don't hash here - let updateTeacherUserAccount() handle it
    }
    // If we're updating email, we need to provide all required fields for the updateTeacher method
    // This is because the method validates all required fields
    if (isset($updateData['email']) || isset($updateData['full_name']) || isset($updateData['phone_number']) || 
        isset($updateData['gender']) || isset($updateData['hourly_rate']) || isset($updateData['monthly_salary']) || 
        isset($updateData['password'])) {
        
        // Get current teacher data to fill in missing required fields
        $teacher = new Teacher($db);
        $current_teacher_result = $teacher->getTeacherById($teacher_id);
        
        if (!$current_teacher_result['success']) {
            throw new Exception('Teacher not found');
        }
        
        $current_teacher = $current_teacher_result['teacher'];
        
        // Merge current data with update data, ensuring all required fields are present
        $completeUpdateData = [
            'full_name' => $updateData['full_name'] ?? $current_teacher['full_name'],
            'phone_number' => $updateData['phone_number'] ?? $current_teacher['phone_number'],
            'email' => $updateData['email'] ?? $current_teacher['email'],
            'gender' => $updateData['gender'] ?? $current_teacher['gender'],
            'hourly_rate' => $updateData['hourly_rate'] ?? $current_teacher['hourly_rate'],
            'monthly_salary' => $updateData['monthly_salary'] ?? $current_teacher['monthly_salary']
        ];
        
        // Add password if it's being updated
        if (isset($updateData['password'])) {
            $completeUpdateData['password'] = $updateData['password'];
        }
        
        // Update the teacher
        $result = $teacher->updateTeacher($teacher_id, $completeUpdateData);
        
        if ($result['success']) {
            // Get updated teacher data to return
            $updated_teacher_result = $teacher->getTeacherById($teacher_id);
            $updated_teacher = $updated_teacher_result['success'] ? $updated_teacher_result['teacher'] : null;
            
            echo json_encode([
                'success' => true,
                'message' => $result['message'],
                'teacher_id' => $teacher_id,
                'updated_fields' => array_keys($updateData),
                'teacher' => $updated_teacher
            ]);
        } else {
            throw new Exception($result['message']);
        }
    } else {
        throw new Exception('No valid fields provided for update');
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
