<?php
require_once 'Database.php';

class Sessions {
    private $db;
    private $id;
    private $session_name;
    private $teacher_id;
    private $student_id;
    private $classroom_id;
    private $date;
    private $created_at;
    private $updated_at;

    // Constructor
    public function __construct($database) {
        $this->db = $database;
    }

    // Getters
    public function getId() {
        return $this->id;
    }

    public function getSessionName() {
        return $this->session_name;
    }

    public function getTeacherId() {
        return $this->teacher_id;
    }

    public function getStudentId() {
        return $this->student_id;
    }

    public function getClassroomId() {
        return $this->classroom_id;
    }

    public function getDate() {
        return $this->date;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function getUpdatedAt() {
        return $this->updated_at;
    }

    // Setters
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function setSessionName($session_name) {
        $this->session_name = trim($session_name);
        return $this;
    }

    public function setTeacherId($teacher_id) {
        $this->teacher_id = $teacher_id;
        return $this;
    }

    public function setStudentId($student_id) {
        $this->student_id = $student_id;
        return $this;
    }

    public function setClassroomId($classroom_id) {
        $this->classroom_id = $classroom_id;
        return $this;
    }

    public function setDate($date) {
        $this->date = $date;
        return $this;
    }

    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
        return $this;
    }

    public function setUpdatedAt($updated_at) {
        $this->updated_at = $updated_at;
        return $this;
    }

    // Validate session data
    private function validateSessionData() {
        $errors = [];

        // Required field validation
        if (empty($this->session_name)) {
            $errors[] = "Session name is required";
        } elseif (strlen($this->session_name) < 2) {
            $errors[] = "Session name must be at least 2 characters long";
        } elseif (strlen($this->session_name) > 100) {
            $errors[] = "Session name must not exceed 100 characters";
        }

        if (empty($this->teacher_id)) {
            $errors[] = "Teacher ID is required";
        }

        if (empty($this->student_id)) {
            $errors[] = "Student ID is required";
        }

        if (empty($this->classroom_id)) {
            $errors[] = "Classroom ID is required";
        }

        return $errors;
    }

    // Add session to database (single session without student_id)
    public function add_Session($session_data = null) {
        try {
            // Set data if provided
            if ($session_data !== null) {
                $this->setSessionName($session_data['session_name'] ?? '');
                $this->setTeacherId($session_data['teacher_id'] ?? '');
                $this->setClassroomId($session_data['classroom_id'] ?? '');
                $this->setDate($session_data['date'] ?? date('Y-m-d'));
            }

            // Validate data (excluding student_id since we'll create for all students)
            $validation_errors = $this->validateSessionDataForClassroom();
            if (!empty($validation_errors)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validation_errors),
                    'errors' => $validation_errors
                ];
            }

            // Verify teacher exists
            $teacher_check_sql = "SELECT id FROM teachers WHERE id = :teacher_id";
            $teacher_check_stmt = $this->db->prepare($teacher_check_sql);
            $teacher_check_stmt->bindParam(':teacher_id', $this->teacher_id, PDO::PARAM_INT);
            $teacher_check_stmt->execute();
            
            if ($teacher_check_stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Teacher not found'];
            }

            // Verify classroom exists
            $classroom_check_sql = "SELECT id FROM classrooms WHERE id = :classroom_id";
            $classroom_check_stmt = $this->db->prepare($classroom_check_sql);
            $classroom_check_stmt->bindParam(':classroom_id', $this->classroom_id, PDO::PARAM_INT);
            $classroom_check_stmt->execute();
            
            if ($classroom_check_stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Classroom not found'];
            }

            // Create single session without student_id
            $sql = "INSERT INTO sessions (
                        session_name, 
                        teacher_id, 
                        classroom_id, 
                        date, 
                        created_at, 
                        updated_at
                    ) VALUES (
                        :session_name, 
                        :teacher_id, 
                        :classroom_id, 
                        :date, 
                        NOW(), 
                        NOW()
                    )";

            $stmt = $this->db->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':session_name', $this->session_name, PDO::PARAM_STR);
            $stmt->bindParam(':teacher_id', $this->teacher_id, PDO::PARAM_INT);
            $stmt->bindParam(':classroom_id', $this->classroom_id, PDO::PARAM_INT);
            $stmt->bindParam(':date', $this->date, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $session_id = $this->db->lastInsertId();

                return [
                    'success' => true,
                    'message' => 'Session created successfully',
                    'session_ids' => [$session_id], // Keep array format for backward compatibility
                    'created_sessions' => [
                        [
                            'session_id' => $session_id,
                            'teacher_id' => $this->getTeacherId(),
                            'classroom_id' => $this->getClassroomId()
                        ]
                    ],
                    'data' => [
                        'session_name' => $this->getSessionName(),
                        'teacher_id' => $this->getTeacherId(),
                        'classroom_id' => $this->getClassroomId(),
                        'date' => $this->getDate()
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create session',
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

    // Validate session data for classroom (without student_id requirement)
    private function validateSessionDataForClassroom() {
        $errors = [];

        // Required field validation
        if (empty($this->session_name)) {
            $errors[] = "Session name is required";
        } elseif (strlen($this->session_name) < 2) {
            $errors[] = "Session name must be at least 2 characters long";
        } elseif (strlen($this->session_name) > 100) {
            $errors[] = "Session name must not exceed 100 characters";
        }

        if (empty($this->teacher_id)) {
            $errors[] = "Teacher ID is required";
        }

        if (empty($this->classroom_id)) {
            $errors[] = "Classroom ID is required";
        }

        return $errors;
    }

    // Get sessions by teacher
    public function getSessionsByTeacher($teacher_id, $limit = null, $offset = 0) {
        try {
            $sql = "SELECT s.*, c.name as classroom_name 
                    FROM sessions s 
                    LEFT JOIN classrooms c ON s.classroom_id = c.id 
                    WHERE s.teacher_id = :teacher_id 
                    ORDER BY s.date DESC, s.created_at DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'sessions' => $sessions,
                'count' => count($sessions)
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getSessionsByTeacher: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get sessions by student (now returns sessions for the student's classroom)
    public function getSessionsByStudent($student_id, $limit = null, $offset = 0) {
        try {
            // First get the student's classroom_id
            $student_sql = "SELECT classroom_id FROM students WHERE id = :student_id";
            $student_stmt = $this->db->prepare($student_sql);
            $student_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $student_stmt->execute();
            
            $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                return [
                    'success' => false,
                    'message' => 'Student not found'
                ];
            }
            
            // Now get sessions for the student's classroom
            $sql = "SELECT s.*, t.full_name as teacher_name, c.name as classroom_name 
                    FROM sessions s 
                    LEFT JOIN teachers t ON s.teacher_id = t.id 
                    LEFT JOIN classrooms c ON s.classroom_id = c.id 
                    WHERE s.classroom_id = :classroom_id 
                    ORDER BY s.date DESC, s.created_at DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':classroom_id', $student['classroom_id'], PDO::PARAM_INT);
            
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'sessions' => $sessions,
                'count' => count($sessions)
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getSessionsByStudent: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get sessions by classroom (shows unique sessions for the classroom)
    public function getSessionsByClassroom($classroom_id, $limit = null, $offset = 0) {
        try {
            $sql = "SELECT DISTINCT s.id, s.session_name, s.date, s.created_at, s.updated_at,
                           t.full_name as teacher_name, c.name as classroom_name,
                           (SELECT COUNT(*) FROM students WHERE classroom_id = :classroom_id) as student_count
                    FROM sessions s 
                    LEFT JOIN teachers t ON s.teacher_id = t.id 
                    LEFT JOIN classrooms c ON s.classroom_id = c.id 
                    WHERE s.classroom_id = :classroom_id 
                    ORDER BY s.date DESC, s.created_at DESC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'sessions' => $sessions,
                'count' => count($sessions)
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getSessionsByClassroom: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get session by ID
    public function getSessionById($session_id) {
        try {
            $sql = "SELECT s.*, t.full_name as teacher_name, c.name as classroom_name 
                    FROM sessions s 
                    LEFT JOIN teachers t ON s.teacher_id = t.id 
                    LEFT JOIN classrooms c ON s.classroom_id = c.id 
                    WHERE s.id = :session_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':session_id', $session_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                return [
                    'success' => true,
                    'session' => $session
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Session not found'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in getSessionById: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
?>
