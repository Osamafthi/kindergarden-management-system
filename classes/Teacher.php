<?php
require_once 'Database.php';
class Teacher extends User {
    // Teacher-specific properties
    private $user_id;
    private $full_name;
    private $phone_number;
    private $gender;
    private $date_of_hire;

    private $hourly_rate;
    private $monthly_salary;
    private $employment_type;
    private $notes;

    // Constructor
    public function __construct($database) {
        parent::__construct($database); // Call parent constructor
    }

    // Getters for Teacher-specific properties
    public function getUserId() {
        return $this->user_id;
    }

    public function getFullName() {
        return $this->full_name;
    }

    public function getPhoneNumber() {
        return $this->phone_number;
    }

    public function getGender() {
        return $this->gender;
    }

    public function getDateOfHire() {
        return $this->date_of_hire;
    }

  

    public function getHourlyRate() {
        return $this->hourly_rate;
    }

    public function getMonthlySalary() {
        return $this->monthly_salary;
    }

    public function getEmploymentType() {
        return $this->employment_type;
    }

    public function getNotes() {
        return $this->notes;
    }

    // Setters for Teacher-specific properties
    public function setUserId($user_id) {
        $this->user_id = $user_id;
        return $this;
    }

    public function setFullName($full_name) {
        $this->full_name = $full_name;
        return $this;
    }

    public function setPhoneNumber($phone_number) {
        $this->phone_number = $phone_number;
        return $this;
    }

    public function setGender($gender) {
        $this->gender = $gender;
        return $this;
    }

    public function setDateOfHire($date_of_hire) {
        $this->date_of_hire = $date_of_hire;
        return $this;
    }

    

    public function setHourlyRate($hourly_rate) {
        $this->hourly_rate = $hourly_rate;
        return $this;
    }

    public function setMonthlySalary($monthly_salary) {
        $this->monthly_salary = $monthly_salary;
        return $this;
    }

    public function setEmploymentType($employment_type) {
        $this->employment_type = $employment_type;
        return $this;
    }

    public function setNotes($notes) {
        $this->notes = $notes;
        return $this;
    }

    // Override fromArray to include both parent and child properties
    public function fromArray($data) {
        parent::fromArray($data); // Handle parent properties
        
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method) && $method !== 'setEmail') {
                $this->$method($value);
            }
        }
        return $this;
    }

    // Override toArray to include both parent and child properties
    public function toArray() {
        $parentData = parent::toArray();
        $childData = [
            'user_id' => $this->user_id,
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'gender' => $this->gender,
            'date_of_hire' => $this->date_of_hire,
           
            'hourly_rate' => $this->hourly_rate,
            'monthly_salary' => $this->monthly_salary,
            'employment_type' => $this->employment_type,
            'notes' => $this->notes
        ];
        
        return array_merge($parentData, $childData);
    }

    // Teacher-specific methods
    public function calculateMonthlyEarnings() {
        if ($this->employment_type === 'hourly' && $this->hourly_rate) {
            // Assuming 160 working hours per month (40 hours/week × 4 weeks)
            return $this->hourly_rate * 160;
        }
        return $this->monthly_salary;
    }

    public function addTeacher($data) {
        try {
            // Validate required fields
            $required_fields = ['full_name', 'phone_number', 'email', 'password', 'gender',  'hourly_rate', 'monthly_salary'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Missing required field: $field"];
                }
            }
            
            // Validate password length
            if (strlen($data['password']) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format.'];
            }

            // Validate phone number
            $phone = preg_replace('/[^0-9]/', '', $data['phone_number']);
            if (strlen($phone) < 10) {
                return ['success' => false, 'message' => 'Phone number must be at least 10 digits.'];
            }

            // Validate numeric values
            if (!is_numeric($data['hourly_rate']) || $data['hourly_rate'] < 0) {
                return ['success' => false, 'message' => 'Hourly rate must be a positive number.'];
            }

            if (!is_numeric($data['monthly_salary']) || $data['monthly_salary'] < 0) {
                return ['success' => false, 'message' => 'Monthly salary must be a positive number.'];
            }
    
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM teachers WHERE email = :email");
            $stmt->bindParam(':email', $data['email']);
            $stmt->execute();
          
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'A teacher with this email already exists.'];
            }
        
            // Prepare SQL query
            $sql = "INSERT INTO teachers (full_name, phone_number, email, gender,  hourly_rate, monthly_salary, date_of_hire, created_at) 
                    VALUES (:full_name, :phone_number, :email, :gender,  :hourly_rate, :monthly_salary, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            
            // Bind parameters
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':phone_number', $data['phone_number']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':gender', $data['gender']);
           
            $stmt->bindParam(':hourly_rate', $data['hourly_rate']);
            $stmt->bindParam(':monthly_salary', $data['monthly_salary']);
            
            // Execute query
            if ($stmt->execute()) {
                $teacher_id = $this->db->lastInsertId();
                
                // Also create a user account for the teacher
                $user_result = $this->createTeacherUserAccount($data, $teacher_id);
                
                if (!$user_result['success']) {
                    return $user_result;
                }
                
                return [
                    'success' => true, 
                    'message' => 'Teacher added successfully!',
                    'teacher_id' => $teacher_id,
                    'user_id' => $user_result['user_id']
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to add teacher.'];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in addTeacher: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Error in addTeacher: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    // Create a user account for the teacher
    private function createTeacherUserAccount($data, $teacher_id) {
        try {
            // Check if password is provided
            if (empty($data['password'])) {
                return ['success' => false, 'message' => 'Password is required for user account.'];
            }
            
            // Hash the password
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Default role for teachers
            $role = 'teacher';
            
            $sql = "INSERT INTO users ( email, password, role, teacher_id, created_at) 
                    VALUES ( :email, :password, :role, :teacher_id, NOW())";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':teacher_id', $teacher_id);
            
            if ($stmt->execute()) {
                $user_id = $this->db->lastInsertId();
                
                // Update the teacher record with the user_id
                $update_sql = "UPDATE teachers SET user_id = :user_id WHERE id = :teacher_id";
                $update_stmt = $this->db->prepare($update_sql);
                $update_stmt->bindParam(':user_id', $user_id);
                $update_stmt->bindParam(':teacher_id', $teacher_id);
                $update_stmt->execute();
                
                return [
                    'success' => true,
                    'user_id' => $user_id
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create user account for teacher.'];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in createTeacherUserAccount: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error creating user account: ' . $e->getMessage()];
        }
    }

    // Update teacher information
    public function updateTeacher($teacher_id, $data) {
        try {
            // Validate required fields
            $required_fields = ['full_name', 'phone_number', 'email', 'gender', 'hourly_rate', 'monthly_salary'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Missing required field: $field"];
                }
            }

            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format.'];
            }

            // Validate phone number
            $phone = preg_replace('/[^0-9]/', '', $data['phone_number']);
            if (strlen($phone) < 10) {
                return ['success' => false, 'message' => 'Phone number must be at least 10 digits.'];
            }

            // Validate numeric values
            if (!is_numeric($data['hourly_rate']) || $data['hourly_rate'] < 0) {
                return ['success' => false, 'message' => 'Hourly rate must be a positive number.'];
            }

            if (!is_numeric($data['monthly_salary']) || $data['monthly_salary'] < 0) {
                return ['success' => false, 'message' => 'Monthly salary must be a positive number.'];
            }

            // Check if teacher exists
            $check_sql = "SELECT id FROM teachers WHERE id = :teacher_id";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $check_stmt->execute();

            if ($check_stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Teacher not found.'];
            }

            // Check if email already exists for another teacher
            $email_check_sql = "SELECT id FROM teachers WHERE email = :email AND id != :teacher_id";
            $email_check_stmt = $this->db->prepare($email_check_sql);
            $email_check_stmt->bindParam(':email', $data['email']);
            $email_check_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $email_check_stmt->execute();

            if ($email_check_stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'A teacher with this email already exists.'];
            }

            // Prepare SQL query for updating teacher
            $sql = "UPDATE teachers SET 
                    full_name = :full_name, 
                    phone_number = :phone_number, 
                    email = :email, 
                    gender = :gender, 
                    hourly_rate = :hourly_rate, 
                    monthly_salary = :monthly_salary,
                    updated_at = NOW()
                    WHERE id = :teacher_id";

            $stmt = $this->db->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':full_name', $data['full_name']);
            $stmt->bindParam(':phone_number', $data['phone_number']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':gender', $data['gender']);
            $stmt->bindParam(':hourly_rate', $data['hourly_rate']);
            $stmt->bindParam(':monthly_salary', $data['monthly_salary']);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);

            // Execute query
            if ($stmt->execute()) {
                // Also update the user account email and password if they exist
                $password = isset($data['password']) ? $data['password'] : null;
                $user_update_result = $this->updateTeacherUserAccount($teacher_id, $data['email'], $password);
                
                if (!$user_update_result['success']) {
                    // Log the error but don't fail the teacher update
                    error_log("Warning: Failed to update user account for teacher ID $teacher_id: " . $user_update_result['message']);
                }

                return [
                    'success' => true, 
                    'message' => 'Teacher updated successfully!',
                    'teacher_id' => $teacher_id
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update teacher.'];
            }

        } catch (PDOException $e) {
            error_log("Database error in updateTeacher: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Error in updateTeacher: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Update user account for the teacher
    private function updateTeacherUserAccount($teacher_id, $email, $password = null) {
        try {
            // Get the user_id for this teacher
            $get_user_sql = "SELECT user_id FROM teachers WHERE id = :teacher_id";
            $get_user_stmt = $this->db->prepare($get_user_sql);
            $get_user_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $get_user_stmt->execute();
            
            $teacher_data = $get_user_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$teacher_data || !$teacher_data['user_id']) {
                return ['success' => false, 'message' => 'No user account found for this teacher.'];
            }
            
            $user_id = $teacher_data['user_id'];
            
            // Build update query based on what needs to be updated
            $update_fields = ['email = :email'];
            $params = [':email' => $email, ':user_id' => $user_id];
            
            if ($password && !empty($password)) {
                // Hash the password before storing
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_fields[] = 'password = :password';
                $params[':password'] = $hashed_password;
            }
            
            $update_user_sql = "UPDATE users SET " . implode(', ', $update_fields) . ", updated_at = NOW() WHERE id = :user_id";
            $update_user_stmt = $this->db->prepare($update_user_sql);
            
            foreach ($params as $key => $value) {
                $update_user_stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            
            if ($update_user_stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'User account updated successfully'
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update user account.'];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in updateTeacherUserAccount: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error updating user account: ' . $e->getMessage()];
        }
    }

    // Get all teachers from the database (filter by is_active status)
    public function getAllTeachers($limit = null, $offset = 0, $is_active = 1) {
        try {
            $sql = "SELECT t.*, u.role, u.created_at as user_created_at 
                    FROM teachers t 
                    LEFT JOIN users u ON t.user_id = u.id 
                    WHERE t.is_active = :is_active
                    ORDER BY t.created_at DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
            
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'teachers' => $teachers,
                'count' => count($teachers)
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getAllTeachers: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get a specific teacher by ID
    public function getTeacherById($teacher_id) {
        try {
            $sql = "SELECT t.*, u.role, u.created_at as user_created_at 
                    FROM teachers t 
                    LEFT JOIN users u ON t.user_id = u.id 
                    WHERE t.id = :teacher_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($teacher) {
                return [
                    'success' => true,
                    'teacher' => $teacher
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Teacher not found'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in getTeacherById: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Search teachers by name or email (filter by is_active status)
    public function searchTeachers($search_term, $limit = null, $offset = 0, $is_active = 1) {
        try {
            $sql = "SELECT t.*, u.role, u.created_at as user_created_at 
                    FROM teachers t 
                    LEFT JOIN users u ON t.user_id = u.id 
                    WHERE t.is_active = :is_active
                    AND (t.full_name LIKE :search_term 
                         OR t.email LIKE :search_term 
                         OR t.phone_number LIKE :search_term)
                    ORDER BY t.created_at DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->db->prepare($sql);
            $search_pattern = '%' . $search_term . '%';
            $stmt->bindParam(':search_term', $search_pattern, PDO::PARAM_STR);
            $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
            
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'teachers' => $teachers,
                'count' => count($teachers)
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in searchTeachers: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get total count of teachers
    public function getTeachersCount() {
        try {
            $sql = "SELECT COUNT(*) as total FROM teachers";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'count' => (int)$result['total']
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getTeachersCount: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Update teacher status (active/inactive)
    public function updateTeacherStatus($teacher_id, $is_active) {
        try {
            $sql = "UPDATE teachers SET is_active = :is_active, updated_at = NOW() WHERE id = :teacher_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Teacher status updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update teacher status'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in updateTeacherStatus: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
