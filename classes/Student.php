<?php
require_once 'Database.php';
class Student {
    private $db;
    private $first_name;
    private $last_name;
    private $date_of_birth;
    private $gender;
    private $enrollment_date;
    private $photo;
    private $student_level;
    private $student_id;
    private $created_at;
    private $updated_at;

    // Constructor
    public function __construct($database) {
        $this->db = $database;
    }

    // Getters
    public function getStudentId() {
        return $this->student_id;
    }

    public function getFirstName() {
        return $this->first_name;
    }

    public function getLastName() {
        return $this->last_name;
    }

    public function getDateOfBirth() {
        return $this->date_of_birth;
    }

    public function getGender() {
        return $this->gender;
    }

    public function getEnrollmentDate() {
        return $this->enrollment_date;
    }

    public function getPhoto() {
        return $this->photo;
    }

    public function getStudentLevel() {
        return $this->student_level;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function getUpdatedAt() {
        return $this->updated_at;
    }

    // Setters
    public function setStudentId($student_id) {
        $this->student_id = $student_id;
    }

    public function setFirstName($first_name) {
        $this->first_name = trim($first_name);
    }

    public function setLastName($last_name) {
        $this->last_name = trim($last_name);
    }

    public function setDateOfBirth($date_of_birth) {
        $this->date_of_birth = $date_of_birth;
    }

    public function setGender($gender) {
        $this->gender = $gender;
    }

    public function setEnrollmentDate($enrollment_date) {
        $this->enrollment_date = $enrollment_date;
    }

    public function setPhoto($photo) {
        $this->photo = $photo;
    }

    public function setStudentLevel($student_level) {
        $this->student_level = $student_level;
    }

    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
    }

    public function setUpdatedAt($updated_at) {
        $this->updated_at = $updated_at;
    }

    // Get full name
    public function getFullName() {
        return $this->first_name . ' ' . $this->last_name;
    }

    // Set all student data from array
    public function setStudentData($student_data) {
        $this->setFirstName($student_data['firstName'] ?? '');
        $this->setLastName($student_data['lastName'] ?? '');
        $this->setDateOfBirth($student_data['dateOfBirth'] ?? '');
        $this->setGender($student_data['gender'] ?? '');
        // Don't set enrollment date for updates - it should remain unchanged
        if (isset($student_data['enrollmentDate'])) {
            $this->setEnrollmentDate($student_data['enrollmentDate']);
        }
        $this->setPhoto($student_data['photo'] ?? '');
        $this->setStudentLevel($student_data['studentLevel'] ?? '');
        if (isset($student_data['studentId'])) {
            $this->setStudentId($student_data['studentId']);
        }
    }

    // Calculate age based on date of birth
    public function getAge() {
        if (!$this->date_of_birth) {
            return null;
        }
        
        $birth_date = new DateTime($this->date_of_birth);
        $current_date = new DateTime();
        $age = $current_date->diff($birth_date);
        
        return $age->y;
    }

    // Validate student data
    private function validateStudentData() {
        $errors = [];

        // Required field validation
        if (empty($this->first_name)) {
            $errors[] = "First name is required";
        }

        if (empty($this->last_name)) {
            $errors[] = "Last name is required";
        }

        if (empty($this->date_of_birth)) {
            $errors[] = "Date of birth is required";
        } else {
            // Validate date format and age
          
        }

        if (empty($this->gender) || !in_array($this->gender, ['male', 'female'])) {
            $errors[] = "Valid gender selection is required";
        }

        // Only validate enrollment date if it's being set (for new students)
        if (!empty($this->enrollment_date)) {
            // Validate enrollment date
            $enrollment = DateTime::createFromFormat('Y-m-d', $this->enrollment_date);
            if (!$enrollment) {
                $errors[] = "Invalid enrollment date format";
            } else {
                $current_date = new DateTime();
                if ($enrollment > $current_date) {
                    $errors[] = "Enrollment date cannot be in the future";
                }
            }
        }

        if (empty($this->student_level)) {
            $errors[] = "Student level is required";
        } else {
            $valid_levels = ['pre-k', 'kindergarten', 'beginner', 'intermediate', 'advanced'];
            if (!in_array($this->student_level, $valid_levels)) {
                $errors[] = "Invalid student level";
            }
        }

        return $errors;
    }

    // Add student to database
    public function add_student($student_data = null) {
        try {
            // Validate data before inserting
            
            if ($student_data !== null) {
                $this->setFirstName($student_data['firstName'] ?? '');
                $this->setLastName($student_data['lastName'] ?? '');
                $this->setDateOfBirth($student_data['dateOfBirth'] ?? '');
                $this->setGender($student_data['gender'] ?? '');
                $this->setEnrollmentDate($student_data['enrollmentDate'] ?? '');
                $this->setPhoto($student_data['photo'] ?? '');
                $this->setStudentLevel($student_data['studentLevel'] ?? '');
            }

            $validation_errors = $this->validateStudentData();
            if (!empty($validation_errors)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validation_errors),
                    'errors' => $validation_errors
                ];
            }

            // Prepare SQL statement
            $sql = "INSERT INTO students (
                        first_name, 
                        last_name, 
                        date_of_birth, 
                        gender, 
                        enrollment_date, 
                        photo, 
                        student_level_at_enrollment,
                        academic_year_id,
                        created_at,
                        updated_at
                    ) VALUES (
                        :first_name, 
                        :last_name, 
                        :date_of_birth, 
                        :gender, 
                        :enrollment_date, 
                        :photo, 
                        :student_level,
                        :academic_year_id,
                        NOW(),
                        NOW()
                    )";

            $stmt = $this->db->prepare($sql);
            $academic_year=1;
            // Bind parameters
            $stmt->bindParam(':first_name', $this->first_name, PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $this->last_name, PDO::PARAM_STR);
            $stmt->bindParam(':date_of_birth', $this->date_of_birth, PDO::PARAM_STR);
            $stmt->bindParam(':gender', $this->gender, PDO::PARAM_STR);
            $stmt->bindParam(':enrollment_date', $this->enrollment_date, PDO::PARAM_STR);
            $stmt->bindParam(':photo', $this->photo, PDO::PARAM_STR);
            $stmt->bindParam(':student_level', $this->student_level, PDO::PARAM_STR);
            $stmt->bindParam(':academic_year_id',$academic_year , PDO::PARAM_STR);

            // Execute the statement
            if ($stmt->execute()) {
                // Get the inserted student ID
                $this->student_id = $this->db->lastInsertId();
                $this->created_at = date('Y-m-d H:i:s');
                $this->updated_at = date('Y-m-d H:i:s');

                return [
                    'success' => true,
                    'message' => 'Student added successfully',
                    'student_id' => $this->student_id,
                    'data' => [
                        'firstName' => $this->getFirstName(),
                        'lastName' => $this->getLastName(),
                        'dateOfBirth' => $this->getAge(),
                        'gender' => $this->getGender(),
                        'enrollmentDate' => $this->getEnrollmentDate(),
                        'studentLevel' => $this->getStudentLevel()
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to add student to database',
                    'error_info' => $stmt->errorInfo()
                ];
            }

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }


    // Update student information
    public function updateStudent() {
        try {
            // Validate data before updating
            $validation_errors = $this->validateStudentData();
            if (!empty($validation_errors)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validation_errors),
                    'errors' => $validation_errors
                ];
            }

            $sql = "UPDATE students SET 
                        first_name = :first_name,
                        last_name = :last_name,
                        date_of_birth = :date_of_birth,
                        gender = :gender,
                        photo = :photo,
                        student_level_at_enrollment = :student_level,
                        updated_at = NOW()
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':first_name', $this->first_name, PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $this->last_name, PDO::PARAM_STR);
            $stmt->bindParam(':date_of_birth', $this->date_of_birth, PDO::PARAM_STR);
            $stmt->bindParam(':gender', $this->gender, PDO::PARAM_STR);
            $stmt->bindParam(':photo', $this->photo, PDO::PARAM_STR);
            $stmt->bindParam(':student_level', $this->student_level, PDO::PARAM_STR);
            $stmt->bindParam(':id', $this->student_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $this->updated_at = date('Y-m-d H:i:s');
                
                return [
                    'success' => true,
                    'message' => 'Student updated successfully',
                    'student_id' => $this->student_id
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update student',
                    'error_info' => $stmt->errorInfo()
                ];
            }

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // Delete student
    public function deleteStudent($student_id) {
        try {
            $sql = "DELETE FROM students WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $student_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Student deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete student'
                ];
            }

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // Get all students (only active by default, or all if $status_filter is null)
    public function getAllStudents($limit = null, $offset = 0, $status = 'active') {
        try {
            $sql = "SELECT * FROM students";
            
            // Filter by status if specified
            if ($status !== null) {
                $sql .= " WHERE status = :status";
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }

            $stmt = $this->db->prepare($sql);
            
            // Bind status parameter if specified
            if ($status !== null) {
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            }
            
            if ($limit) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'students' => $results,
                'count' => count($results)
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // Get a specific student by ID
    public function getStudentById($student_id) {
        try {
            $sql = "SELECT * FROM students WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $student_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($student) {
                return [
                    'success' => true,
                    'student' => $student
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Student not found'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in getStudentById: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Search students by name
    public function searchStudents($search_term, $limit = null, $offset = 0, $status = 'active') {
        try {
            $sql = "SELECT * FROM students 
                    WHERE (first_name LIKE :search_term 
                    OR last_name LIKE :search_term 
                    OR CONCAT(first_name, ' ', last_name) LIKE :search_term)";
            
            // Filter by status
            if ($status !== null) {
                $sql .= " AND status = :status";
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->db->prepare($sql);
            $search_pattern = '%' . $search_term . '%';
            $stmt->bindParam(':search_term', $search_pattern, PDO::PARAM_STR);
            
            if ($status !== null) {
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            }
            
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'students' => $students,
                'count' => count($students)
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in searchStudents: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get total count of students
    public function getStudentsCount() {
        try {
            $sql = "SELECT COUNT(*) as total FROM students";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'count' => (int)$result['total']
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getStudentsCount: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Assign student to classroom
    public function assignStudentToClassroom($student_id, $classroom_id) {
        try {
            // Validate input
            if (empty($student_id) || empty($classroom_id)) {
                return ['success' => false, 'message' => 'Student ID and Classroom ID are required'];
            }

            // Check if student exists
            $student_check = $this->getStudentById($student_id);
            if (!$student_check['success']) {
                return ['success' => false, 'message' => 'Student not found'];
            }

            // Check if classroom exists
            $classroom_check_sql = "SELECT id, name, capacity FROM classrooms WHERE id = :classroom_id";
            $classroom_check_stmt = $this->db->prepare($classroom_check_sql);
            $classroom_check_stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            $classroom_check_stmt->execute();
            
            $classroom = $classroom_check_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$classroom) {
                return ['success' => false, 'message' => 'Classroom not found'];
            }

            // Check current classroom capacity
            $capacity_check_sql = "SELECT COUNT(*) as current_count FROM students WHERE classroom_id = :classroom_id";
            $capacity_check_stmt = $this->db->prepare($capacity_check_sql);
            $capacity_check_stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            $capacity_check_stmt->execute();
            
            $current_count = $capacity_check_stmt->fetch(PDO::FETCH_ASSOC)['current_count'];
            
            if ($current_count >= $classroom['capacity']) {
                return ['success' => false, 'message' => 'Classroom is at full capacity'];
            }

            // Check if student is already assigned to any classroom
            $current_assignment_sql = "SELECT classroom_id FROM students WHERE id = :id";
            $current_assignment_stmt = $this->db->prepare($current_assignment_sql);
            $current_assignment_stmt->bindParam(':id', $student_id, PDO::PARAM_INT);
            $current_assignment_stmt->execute();
            
            $current_assignment = $current_assignment_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current_assignment && $current_assignment['classroom_id'] !== null) {
                if ($current_assignment['classroom_id'] == $classroom_id) {
                    return ['success' => false, 'message' => 'Student is already assigned to this classroom'];
                } else {
                    // Get the current classroom name for better error message
                    $current_classroom_sql = "SELECT name FROM classrooms WHERE id = :classroom_id";
                    $current_classroom_stmt = $this->db->prepare($current_classroom_sql);
                    $current_classroom_stmt->bindParam(':classroom_id', $current_assignment['classroom_id'], PDO::PARAM_INT);
                    $current_classroom_stmt->execute();
                    $current_classroom = $current_classroom_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $current_classroom_name = $current_classroom ? $current_classroom['name'] : 'Unknown Classroom';
                    return ['success' => false, 'message' => "Student is already assigned to classroom: {$current_classroom_name}. Please unassign the student first before assigning to a new classroom."];
                }
            }

            // Assign student to classroom
            $assign_sql = "UPDATE students SET classroom_id = :classroom_id, updated_at = NOW() WHERE id = :id";
            $assign_stmt = $this->db->prepare($assign_sql);
            $assign_stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            $assign_stmt->bindParam(':id', $student_id, PDO::PARAM_INT);

            if ($assign_stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Student assigned to classroom successfully',
                    'student_id' => $student_id,
                    'classroom_id' => $classroom_id,
                    'classroom_name' => $classroom['name']
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to assign student to classroom'];
            }

        } catch (PDOException $e) {
            error_log("Database error in assignStudentToClassroom: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Error in assignStudentToClassroom: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Get student's current classroom
    public function getStudentClassroom($student_id) {
        try {
            $sql = "SELECT s.id as student_id, s.first_name, s.last_name, c.id as classroom_id, c.name as classroom_name, c.grade_level, c.capacity 
                    FROM students s 
                    LEFT JOIN classrooms c ON s.classroom_id = c.id 
                    WHERE s.id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $student_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'success' => true,
                    'student' => $result
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Student not found'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in getStudentClassroom: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Unassign student from classroom
    public function unassignStudentFromClassroom($student_id) {
        try {
            // Validate input
            if (empty($student_id)) {
                return ['success' => false, 'message' => 'Student ID is required'];
            }

            // Check if student exists
            $student_check = $this->getStudentById($student_id);
            if (!$student_check['success']) {
                return ['success' => false, 'message' => 'Student not found'];
            }

            // Check if student is currently assigned to any classroom
            $current_assignment_sql = "SELECT classroom_id FROM students WHERE id = :id";
            $current_assignment_stmt = $this->db->prepare($current_assignment_sql);
            $current_assignment_stmt->bindParam(':id', $student_id, PDO::PARAM_INT);
            $current_assignment_stmt->execute();
            
            $current_assignment = $current_assignment_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current_assignment || $current_assignment['classroom_id'] === null) {
                return ['success' => false, 'message' => 'Student is not currently assigned to any classroom'];
            }

            // Get current classroom name for response
            $current_classroom_sql = "SELECT name FROM classrooms WHERE id = :classroom_id";
            $current_classroom_stmt = $this->db->prepare($current_classroom_sql);
            $current_classroom_stmt->bindParam(':classroom_id', $current_assignment['classroom_id'], PDO::PARAM_INT);
            $current_classroom_stmt->execute();
            $current_classroom = $current_classroom_stmt->fetch(PDO::FETCH_ASSOC);
            
            $current_classroom_name = $current_classroom ? $current_classroom['name'] : 'Unknown Classroom';

            // Unassign student from classroom
            $unassign_sql = "UPDATE students SET classroom_id = NULL, updated_at = NOW() WHERE id = :id";
            $unassign_stmt = $this->db->prepare($unassign_sql);
            $unassign_stmt->bindParam(':id', $student_id, PDO::PARAM_INT);

            if ($unassign_stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Student unassigned from classroom successfully',
                    'student_id' => $student_id,
                    'previous_classroom_id' => $current_assignment['classroom_id'],
                    'previous_classroom_name' => $current_classroom_name
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to unassign student from classroom'];
            }

        } catch (PDOException $e) {
            error_log("Database error in unassignStudentFromClassroom: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            error_log("Error in unassignStudentFromClassroom: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    // Get students by classroom ID (only active students by default)
    public function getStudentsByClassroom($classroom_id, $limit = null, $offset = 0, $status = 'active') {
        try {
            $sql = "SELECT s.id, s.first_name, s.last_name, s.date_of_birth, s.gender, s.enrollment_date, s.photo, s.student_level_at_enrollment, s.classroom_id, s.status, s.created_at, s.updated_at,
                           CONCAT(s.first_name, ' ', s.last_name) as full_name
                    FROM students s 
                    WHERE s.classroom_id = :classroom_id";
            
            // Filter by status
            if ($status !== null) {
                $sql .= " AND s.status = :status";
            }
            
            $sql .= " ORDER BY s.first_name ASC, s.last_name ASC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            
            if ($status !== null) {
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            }
            
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'students' => $students,
                'count' => count($students)
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getStudentsByClassroom: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Update student status
    public function updateStudentStatus($student_id, $status) {
        try {
            $sql = "UPDATE students SET status = :status, updated_at = NOW() WHERE id = :student_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Student status updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update student status'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in updateStudentStatus: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}

?>