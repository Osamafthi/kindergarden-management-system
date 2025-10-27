<?php
require_once 'Database.php';

class Homework {
    private $db;
    private $id;
    private $name;
    private $description;
    private $max_grade;
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

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getMaxGrade() {
        return $this->max_grade;
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

    public function setName($name) {
        $this->name = trim($name);
        return $this;
    }

    public function setDescription($description) {
        $this->description = trim($description);
        return $this;
    }

    public function setMaxGrade($max_grade) {
        $this->max_grade = $max_grade;
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

    // Get all homework types
    public function getHomeworkTypes($limit = null, $offset = 0, $type = null) {
        try {
            $sql = "SELECT * FROM homework_types";
            
            // Add WHERE clause if type is specified
            if ($type !== null) {
                $sql .= " WHERE different_types = :type";
            }
            
            $sql .= " ORDER BY name ASC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->db->prepare($sql);
            
            // Bind type parameter if specified
            if ($type !== null) {
                $stmt->bindParam(':type', $type, PDO::PARAM_STR);
            }
            
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $homework_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'homework_types' => $homework_types,
                'count' => count($homework_types)
            ];
            
        }
 catch (PDOException $e) {
            error_log("Database error in getHomeworkTypes: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Get homework type by ID
    public function getHomeworkTypeById($homework_type_id) {
        try {
            $sql = "SELECT * FROM homework_types WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $homework_type_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $homework_type = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($homework_type) {
                return [
                    'success' => true,
                    'homework_type' => $homework_type
                ];
            }
 else {
                return [
                    'success' => false,
                    'message' => 'Homework type not found'
                ];
            }
            
        }
 catch (PDOException $e) {
            error_log("Database error in getHomeworkTypeById: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Add homework type
    public function addHomeworkType($data) {
        try {
            // Validate required fields
            $required_fields = ['name', 'description', 'max_grade', 'different_types'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Missing required field: $field"];
                }
            }

            // Validate name length
            if (strlen($data['name']) < 2) {
                return ['success' => false, 'message' => 'Homework type name must be at least 2 characters long'];
            }

            if (strlen($data['name']) > 100) {
                return ['success' => false, 'message' => 'Homework type name must not exceed 100 characters'];
            }

            // Validate description length
            if (strlen($data['description']) < 10) {
                return ['success' => false, 'message' => 'Description must be at least 10 characters long'];
            }

            // Validate max grade
            if (!is_numeric($data['max_grade']) || $data['max_grade'] < 1 || $data['max_grade'] > 100) {
                return ['success' => false, 'message' => 'Maximum grade must be between 1 and 100'];
            }

            // Validate different_types
            if ($data['different_types'] !== 'quran' && $data['different_types'] !== 'modules') {
                return ['success' => false, 'message' => 'Different types must be either "quran" or "modules"'];
            }

            // Check if homework type name already exists
            $check_sql = "SELECT id FROM homework_types WHERE name = :name";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->bindParam(':name', $data['name']);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'A homework type with this name already exists'];
            }

            // Prepare SQL statement
            $sql = "INSERT INTO homework_types (name, description, max_grade, different_types, created_at, updated_at) 
                    VALUES (:name, :description, :max_grade, :different_types, NOW(), NOW())";

            $stmt = $this->db->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':max_grade', $data['max_grade'], PDO::PARAM_INT);
            $stmt->bindParam(':different_types', $data['different_types'], PDO::PARAM_STR);

            // Execute the statement
            if ($stmt->execute()) {
                // Get the inserted homework type ID
                $this->id = $this->db->lastInsertId();
                $this->created_at = date('Y-m-d H:i:s');
                $this->updated_at = date('Y-m-d H:i:s');

                return [
                    'success' => true,
                    'message' => 'Homework type added successfully',
                    'homework_type_id' => $this->id,
                    'data' => [
                        'name' => $this->getName(),
                        'description' => $this->getDescription(),
                        'max_grade' => $this->getMaxGrade()
                    ]
                ];
            }
 else {
                return [
                    'success' => false,
                    'message' => 'Failed to add homework type',
                    'error_info' => $stmt->errorInfo()
                ];
            }

        }
 catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
 catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    // Update homework type
    public function updateHomeworkType($homework_type_id, $data) {
        try {
            // Validate required fields
            $required_fields = ['name', 'description', 'max_grade'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Missing required field: $field"];
                }
            }

            // Validate name length
            if (strlen($data['name']) < 2) {
                return ['success' => false, 'message' => 'Homework type name must be at least 2 characters long'];
            }

            if (strlen($data['name']) > 100) {
                return ['success' => false, 'message' => 'Homework type name must not exceed 100 characters'];
            }

            // Validate description length
            if (strlen($data['description']) < 10) {
                return ['success' => false, 'message' => 'Description must be at least 10 characters long'];
            }

            // Validate max grade
            if (!is_numeric($data['max_grade']) || $data['max_grade'] < 1 || $data['max_grade'] > 100) {
                return ['success' => false, 'message' => 'Maximum grade must be between 1 and 100'];
            }

            // Check if homework type exists
            $check_sql = "SELECT id FROM homework_types WHERE id = :id";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->bindParam(':id', $homework_type_id, PDO::PARAM_INT);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Homework type not found'];
            }

            // Check if name already exists for another homework type
            $name_check_sql = "SELECT id FROM homework_types WHERE name = :name AND id != :id";
            $name_check_stmt = $this->db->prepare($name_check_sql);
            $name_check_stmt->bindParam(':name', $data['name']);
            $name_check_stmt->bindParam(':id', $homework_type_id, PDO::PARAM_INT);
            $name_check_stmt->execute();
            
            if ($name_check_stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'A homework type with this name already exists'];
            }

            // Prepare SQL statement
            $sql = "UPDATE homework_types SET 
                    name = :name, 
                    description = :description, 
                    max_grade = :max_grade, 
                    updated_at = NOW()
                    WHERE id = :id";

            $stmt = $this->db->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $stmt->bindParam(':max_grade', $data['max_grade'], PDO::PARAM_INT);
            $stmt->bindParam(':id', $homework_type_id, PDO::PARAM_INT);

            // Execute the statement
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Homework type updated successfully',
                    'homework_type_id' => $homework_type_id
                ];
            }
 else {
                return [
                    'success' => false,
                    'message' => 'Failed to update homework type',
                    'error_info' => $stmt->errorInfo()
                ];
            }

        }
 catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
 catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    // Delete homework type
    public function deleteHomeworkType($homework_type_id) {
        try {
            $sql = "DELETE FROM homework_types WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $homework_type_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Homework type deleted successfully'
                ];
            }
 else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete homework type'
                ];
            }

        }
 catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // Get total count of homework types
    public function getHomeworkTypesCount() {
        try {
            $sql = "SELECT COUNT(*) as total FROM homework_types";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'count' => (int)$result['total']
            ];
            
        }
 catch (PDOException $e) {
            error_log("Database error in getHomeworkTypesCount: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Arabic to Western numeral conversion utility
    private function convertArabicToWestern($text) {
        if (!$text) return $text;
        
        $arabicToWestern = [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'
        ];
        
        return str_replace(array_keys($arabicToWestern), array_values($arabicToWestern), $text);
    }

    // Add homework chapter data
    public function addHomeworkChapter($data) {
        try {
            // Convert Arabic numerals to Western numerals
            if (isset($data['quran_from'])) {
                $data['quran_from'] = $this->convertArabicToWestern($data['quran_from']);
            }
            if (isset($data['quran_to'])) {
                $data['quran_to'] = $this->convertArabicToWestern($data['quran_to']);
            }

            // Validate required fields
            $required_fields = ['session_homework_id', 'homework_type_id', 'quran_from', 'quran_chapter', 'classroom_id', 'quran_to', 'quran_suras_id'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                    return ['success' => false, 'message' => "Missing required field: $field"];
                }
            }

            // Validate numeric fields
            if (!is_numeric($data['quran_from']) || $data['quran_from'] < 0) {
                return ['success' => false, 'message' => 'From field must be a positive number'];
            }

            if (!is_numeric($data['quran_to']) || $data['quran_to'] < 0) {
                return ['success' => false, 'message' => 'To field must be a positive number'];
            }

            if ($data['quran_from'] > $data['quran_to']) {
                return ['success' => false, 'message' => 'From field cannot be greater than To field'];
            }

            // Validate chapter name length
            if (strlen($data['quran_chapter']) < 2) {
                return ['success' => false, 'message' => 'Chapter name must be at least 2 characters long'];
            }

            if (strlen($data['quran_chapter']) > 255) {
                return ['success' => false, 'message' => 'Chapter name must not exceed 255 characters'];
            }

            // Prepare SQL statement
            $sql = "INSERT INTO homework_grades (
                        session_homework_id, 
                        homework_type_id,
                        quran_from, 
                        quran_chapter, 
                        classroom_id, 
                        quran_to,
                        quran_suras_id,
                        created_at
                    ) VALUES (
                        :session_homework_id, 
                        :homework_type_id,
                        :quran_from, 
                        :quran_chapter, 
                        :classroom_id, 
                        :quran_to,
                        :quran_suras_id,
                        NOW()
                    )";

            $stmt = $this->db->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':session_homework_id', $data['session_homework_id'], PDO::PARAM_INT);
            $stmt->bindParam(':homework_type_id', $data['homework_type_id'], PDO::PARAM_INT);
            $stmt->bindParam(':quran_from', $data['quran_from'], PDO::PARAM_INT);
            $stmt->bindParam(':quran_chapter', $data['quran_chapter'], PDO::PARAM_STR);
            $stmt->bindParam(':classroom_id', $data['classroom_id'], PDO::PARAM_INT);
            $stmt->bindParam(':quran_to', $data['quran_to'], PDO::PARAM_INT);
            $stmt->bindParam(':quran_suras_id', $data['quran_suras_id'], PDO::PARAM_INT);

            // Execute the statement
            if ($stmt->execute()) {
                $homework_grade_id = $this->db->lastInsertId();

                return [
                    'success' => true,
                    'message' => 'Homework chapter data added successfully',
                    'homework_grade_id' => $homework_grade_id,
                    'data' => [
                        'session_homework_id' => $data['session_homework_id'],
                        'homework_type_id' => $data['homework_type_id'],
                        'quran_from' => $data['quran_from'],
                        'quran_to' => $data['quran_to'],
                        'quran_chapter' => $data['quran_chapter'],
                        'classroom_id' => $data['classroom_id'],
                        'quran_suras_id' => $data['quran_suras_id']
                    ]
                ];
            }
 else {
                return [
                    'success' => false,
                    'message' => 'Failed to add homework chapter data',
                    'error_info' => $stmt->errorInfo()
                ];
            }

        }
 catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
 catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    // Get homework data for a specific session
    public function getHomeworkData($session_name, $session_date, $classroom_id) {
        try {
            $sql = "SELECT 
                        hg.id,
                        hg.session_homework_id,
                        hg.homework_type_id,
                        hg.quran_chapter,
                        hg.quran_from,
                        hg.quran_to,
                        hg.classroom_id,
                        hg.created_at,
                        ht.name as homework_type_name,
                        ht.description as homework_type_description,
                        ht.max_grade,
                        s.id as session_id,
                        s.session_name,
                        s.date as session_date,
                        c.name as classroom_name
                    FROM homework_grades hg
                    LEFT JOIN homework_types ht ON hg.homework_type_id = ht.id
                    LEFT JOIN sessions s ON hg.session_homework_id = s.id
                    LEFT JOIN classrooms c ON hg.classroom_id = c.id
                    WHERE s.session_name = :session_name 
                    AND s.date = :session_date 
                    AND hg.classroom_id = :classroom_id
                    ORDER BY ht.name ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':session_name', $session_name, PDO::PARAM_STR);
            $stmt->bindParam(':session_date', $session_date, PDO::PARAM_STR);
            $stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            $stmt->execute();

            $homework_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'homework_data' => $homework_data,
                'count' => count($homework_data)
            ];

        }
 catch (PDOException $e) {
            error_log("Database error in getHomeworkData: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Add grade homework for a student
    public function addGradeHomework($grades_data) {
        try {
            // Validate required fields
            $required_fields = ['session_id', 'teacher_id', 'student_id', 'grades'];
            foreach ($required_fields as $field) {
                if (!isset($grades_data[$field])) {
                    return ['success' => false, 'message' => "Missing required field: $field"];
                }
            }

            // Validate grades array
            if (!is_array($grades_data['grades']) || empty($grades_data['grades'])) {
                return ['success' => false, 'message' => 'Grades data must be a non-empty array'];
            }

            // Start transaction
            $this->db->beginTransaction();

            $inserted_grades = [];
            $errors = [];

            foreach ($grades_data['grades'] as $grade_item) {
                // Validate each grade item
                if (!isset($grade_item['homework_type_id']) || !isset($grade_item['homework_grades_id'])) {
                    $errors[] = 'Missing homework_type_id or homework_grades_id in grade item';
                    continue;
                }

                // Validate grade value
                $grade = isset($grade_item['grade']) ? $grade_item['grade'] : null;
                if ($grade !== null && (!is_numeric($grade) || $grade < 0)) {
                    $errors[] = 'Invalid grade value for homework type ID: ' . $grade_item['homework_type_id'];
                    continue;
                }

                // Check if grade already exists for this student and homework
                $check_sql = "SELECT id FROM session_homework 
                             WHERE session_id = :session_id 
                             AND student_id = :student_id 
                             AND homework_type_id = :homework_type_id 
                             AND homework_grades_id = :homework_grades_id";
                
                $check_stmt = $this->db->prepare($check_sql);
                $check_stmt->bindParam(':session_id', $grades_data['session_id'], PDO::PARAM_INT);
                $check_stmt->bindParam(':student_id', $grades_data['student_id'], PDO::PARAM_INT);
                $check_stmt->bindParam(':homework_type_id', $grade_item['homework_type_id'], PDO::PARAM_INT);
                $check_stmt->bindParam(':homework_grades_id', $grade_item['homework_grades_id'], PDO::PARAM_INT);
                $check_stmt->execute();

                if ($check_stmt->rowCount() > 0) {
                    // Update existing grade
                    $update_sql = "UPDATE session_homework 
                                   SET grade = :grade, 
                                       teacher_id = :teacher_id,
                                       updated_at = NOW()
                                   WHERE session_id = :session_id 
                                   AND student_id = :student_id 
                                   AND homework_type_id = :homework_type_id 
                                   AND homework_grades_id = :homework_grades_id";
                    
                    $update_stmt = $this->db->prepare($update_sql);
                    $update_stmt->bindParam(':grade', $grade, PDO::PARAM_INT);
                    $update_stmt->bindParam(':teacher_id', $grades_data['teacher_id'], PDO::PARAM_INT);
                    $update_stmt->bindParam(':session_id', $grades_data['session_id'], PDO::PARAM_INT);
                    $update_stmt->bindParam(':student_id', $grades_data['student_id'], PDO::PARAM_INT);
                    $update_stmt->bindParam(':homework_type_id', $grade_item['homework_type_id'], PDO::PARAM_INT);
                    $update_stmt->bindParam(':homework_grades_id', $grade_item['homework_grades_id'], PDO::PARAM_INT);
                    
                    if ($update_stmt->execute()) {
                        $inserted_grades[] = [
                            'homework_type_id' => $grade_item['homework_type_id'],
                            'homework_grades_id' => $grade_item['homework_grades_id'],
                            'grade' => $grade,
                            'action' => 'updated'
                        ];
                    }
 else {
                        $errors[] = 'Failed to update grade for homework type ID: ' . $grade_item['homework_type_id'];
                    }
                }
 else {
                    // Insert new grade
                    $insert_sql = "INSERT INTO session_homework (
                                      session_id, 
                                      homework_type_id, 
                                      teacher_id, 
                                      grade, 
                                      homework_grades_id, 
                                      student_id, 
                                      created_at, 
                                      updated_at
                                   ) VALUES (
                                      :session_id, 
                                      :homework_type_id, 
                                      :teacher_id, 
                                      :grade, 
                                      :homework_grades_id, 
                                      :student_id, 
                                      NOW(), 
                                      NOW()
                                   )";
                    
                    $insert_stmt = $this->db->prepare($insert_sql);
                    $insert_stmt->bindParam(':session_id', $grades_data['session_id'], PDO::PARAM_INT);
                    $insert_stmt->bindParam(':homework_type_id', $grade_item['homework_type_id'], PDO::PARAM_INT);
                    $insert_stmt->bindParam(':teacher_id', $grades_data['teacher_id'], PDO::PARAM_INT);
                    $insert_stmt->bindParam(':grade', $grade, PDO::PARAM_INT);
                    $insert_stmt->bindParam(':homework_grades_id', $grade_item['homework_grades_id'], PDO::PARAM_INT);
                    $insert_stmt->bindParam(':student_id', $grades_data['student_id'], PDO::PARAM_INT);
                    
                    if ($insert_stmt->execute()) {
                        $inserted_grades[] = [
                            'homework_type_id' => $grade_item['homework_type_id'],
                            'homework_grades_id' => $grade_item['homework_grades_id'],
                            'grade' => $grade,
                            'action' => 'inserted',
                            'id' => $this->db->lastInsertId()
                        ];
                    }
 else {
                        $errors[] = 'Failed to insert grade for homework type ID: ' . $grade_item['homework_type_id'];
                    }
                }
            }

            // Check if there were any errors
            if (!empty($errors)) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Some grades could not be saved: ' . implode(', ', $errors),
                    'errors' => $errors
                ];
            }

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Grades saved successfully',
                'inserted_grades' => $inserted_grades,
                'count' => count($inserted_grades)
            ];

        }
 catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Database error in addGradeHomework: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
 catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    // Add homework module data
    public function addHomeworkModule($data) {
        try {
            // Validate required fields
            $required_fields = ['session_homework_id', 'homework_type_id', 'lesson_title', 'photo', 'classroom_id'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                    return ['success' => false, 'message' => "Missing required field: $field"];
                }
            }

            // Validate lesson title length
            if (strlen($data['lesson_title']) < 2) {
                return ['success' => false, 'message' => 'Lesson title must be at least 2 characters long'];
            }

            if (strlen($data['lesson_title']) > 255) {
                return ['success' => false, 'message' => 'Lesson title must not exceed 255 characters'];
            }

            // Validate photo path format (should be a valid file path)
            if (!is_string($data['photo']) || empty($data['photo'])) {
                return ['success' => false, 'message' => 'Photo path is required'];
            }

            // Prepare SQL statement
            $sql = "INSERT INTO session_module (
                        session_homework_id, 
                        homework_types_id,
                        lesson_title, 
                        photo, 
                        classroom_id, 
                        created_at
                    ) VALUES (
                        :session_homework_id, 
                        :homework_types_id,
                        :lesson_title, 
                        :photo, 
                        :classroom_id, 
                        NOW()
                    )";

            $stmt = $this->db->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':session_homework_id', $data['session_homework_id'], PDO::PARAM_INT);
            $stmt->bindParam(':homework_types_id', $data['homework_type_id'], PDO::PARAM_INT);
            $stmt->bindParam(':lesson_title', $data['lesson_title'], PDO::PARAM_STR);
            $stmt->bindParam(':photo', $data['photo'], PDO::PARAM_STR);
            $stmt->bindParam(':classroom_id', $data['classroom_id'], PDO::PARAM_INT);

            // Execute the statement
            if ($stmt->execute()) {
                $module_id = $this->db->lastInsertId();

                return [
                    'success' => true,
                    'message' => 'Homework module data added successfully',
                    'module_id' => $module_id,
                    'data' => [
                        'session_homework_id' => $data['session_homework_id'],
                        'homework_type_id' => $data['homework_type_id'],
                        'lesson_title' => $data['lesson_title'],
                        'photo' => $data['photo'],
                        'classroom_id' => $data['classroom_id']
                    ]
                ];
            }
 else {
                return [
                    'success' => false,
                    'message' => 'Failed to add homework module data',
                    'error_info' => $stmt->errorInfo()
                ];
            }

        }
 catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
 catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    // Get module homework data for a specific session and student
    public function getModuleHomeworkData($session_name, $session_date, $classroom_id, $student_id, $limit = null, $offset = 0) {
        try {
            $sql = "SELECT 
                        sm.id as module_id,
                        sm.session_homework_id,
                        sm.lesson_title,
                        sm.photo,
                        sm.homework_types_id,
                        sm.classroom_id,
                        sm.created_at,
                        ht.name as homework_type_name,
                        ht.max_grade,
                        ht.description as homework_description,
                        s.session_name,
                        s.date as session_date,
                        s.id as session_id,
                        c.name as classroom_name
                    FROM session_module sm
                    LEFT JOIN sessions s ON sm.session_homework_id = s.id
                    LEFT JOIN homework_types ht ON sm.homework_types_id = ht.id
                    LEFT JOIN classrooms c ON sm.classroom_id = c.id
                    WHERE s.session_name = :session_name 
                    AND s.date = :session_date 
                    AND sm.classroom_id = :classroom_id
                    AND ht.different_types = 'modules'
                    ORDER BY sm.created_at ASC";
            
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':session_name', $session_name, PDO::PARAM_STR);
            $stmt->bindParam(':session_date', $session_date, PDO::PARAM_STR);
            $stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            
            if ($limit !== null) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $homework_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'homework_data' => $homework_data,
                'count' => count($homework_data)
            ];
            
        }
 catch (PDOException $e) {
            error_log("Database error in getModuleHomeworkData: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Add grade homework for modules (session_module table)
    public function addGradeHomeworkModule($grades_data) {
        try {
            // Validate required fields
            $required_fields = ['session_id', 'teacher_id', 'student_id', 'grades'];
            foreach ($required_fields as $field) {
                if (!isset($grades_data[$field])) {
                    return ['success' => false, 'message' => "Missing required field: $field"];
                }
            }

            // Validate grades array
            if (!is_array($grades_data['grades']) || empty($grades_data['grades'])) {
                return ['success' => false, 'message' => 'Grades data must be a non-empty array'];
            }

            // Start transaction
            $this->db->beginTransaction();

            $inserted_grades = [];
            $errors = [];

            foreach ($grades_data['grades'] as $grade_item) {
                // Validate each grade item
                if (!isset($grade_item['homework_type_id']) || !isset($grade_item['session_module_id'])) {
                    $errors[] = 'Missing homework_type_id or session_module_id in grade item';
                    continue;
                }

                // Validate grade value
                $grade = isset($grade_item['grade']) ? $grade_item['grade'] : null;
                if ($grade !== null && (!is_numeric($grade) || $grade < 0)) {
                    $errors[] = 'Invalid grade value for homework type ID: ' . $grade_item['homework_type_id'];
                    continue;
                }

                // Check if grade already exists for this student and module
                $check_sql = "SELECT id FROM session_homework 
                             WHERE session_id = :session_id 
                             AND student_id = :student_id 
                             AND homework_type_id = :homework_type_id 
                             AND session_module_id = :session_module_id";
                
                $check_stmt = $this->db->prepare($check_sql);
                $check_stmt->bindParam(':session_id', $grades_data['session_id'], PDO::PARAM_INT);
                $check_stmt->bindParam(':student_id', $grades_data['student_id'], PDO::PARAM_INT);
                $check_stmt->bindParam(':homework_type_id', $grade_item['homework_type_id'], PDO::PARAM_INT);
                $check_stmt->bindParam(':session_module_id', $grade_item['session_module_id'], PDO::PARAM_INT);
                $check_stmt->execute();

                if ($check_stmt->rowCount() > 0) {
                    // Update existing grade
                    $update_sql = "UPDATE session_homework 
                                   SET grade = :grade, 
                                       teacher_id = :teacher_id,
                                       updated_at = NOW()
                                   WHERE session_id = :session_id 
                                   AND student_id = :student_id 
                                   AND homework_type_id = :homework_type_id 
                                   AND session_module_id = :session_module_id";
                    
                    $update_stmt = $this->db->prepare($update_sql);
                    $update_stmt->bindParam(':grade', $grade, PDO::PARAM_INT);
                    $update_stmt->bindParam(':teacher_id', $grades_data['teacher_id'], PDO::PARAM_INT);
                    $update_stmt->bindParam(':session_id', $grades_data['session_id'], PDO::PARAM_INT);
                    $update_stmt->bindParam(':student_id', $grades_data['student_id'], PDO::PARAM_INT);
                    $update_stmt->bindParam(':homework_type_id', $grade_item['homework_type_id'], PDO::PARAM_INT);
                    $update_stmt->bindParam(':session_module_id', $grade_item['session_module_id'], PDO::PARAM_INT);
                    
                    if ($update_stmt->execute()) {
                        $inserted_grades[] = [
                            'homework_type_id' => $grade_item['homework_type_id'],
                            'session_module_id' => $grade_item['session_module_id'],
                            'grade' => $grade,
                            'action' => 'updated'
                        ];
                    }
 else {
                        $errors[] = 'Failed to update grade for homework type ID: ' . $grade_item['homework_type_id'];
                    }
                }
 else {
                    // Insert new grade
                    $insert_sql = "INSERT INTO session_homework (
                                      session_id, 
                                      homework_type_id, 
                                      teacher_id, 
                                      grade, 
                                      session_module_id, 
                                      student_id, 
                                      created_at, 
                                      updated_at
                                   ) VALUES (
                                      :session_id, 
                                      :homework_type_id, 
                                      :teacher_id, 
                                      :grade, 
                                      :session_module_id, 
                                      :student_id, 
                                      NOW(), 
                                      NOW()
                                   )";
                    
                    $insert_stmt = $this->db->prepare($insert_sql);
                    $insert_stmt->bindParam(':session_id', $grades_data['session_id'], PDO::PARAM_INT);
                    $insert_stmt->bindParam(':homework_type_id', $grade_item['homework_type_id'], PDO::PARAM_INT);
                    $insert_stmt->bindParam(':teacher_id', $grades_data['teacher_id'], PDO::PARAM_INT);
                    $insert_stmt->bindParam(':grade', $grade, PDO::PARAM_INT);
                    $insert_stmt->bindParam(':session_module_id', $grade_item['session_module_id'], PDO::PARAM_INT);
                    $insert_stmt->bindParam(':student_id', $grades_data['student_id'], PDO::PARAM_INT);
                    
                    if ($insert_stmt->execute()) {
                        $inserted_grades[] = [
                            'homework_type_id' => $grade_item['homework_type_id'],
                            'session_module_id' => $grade_item['session_module_id'],
                            'grade' => $grade,
                            'action' => 'inserted',
                            'id' => $this->db->lastInsertId()
                        ];
                    }
 else {
                        $errors[] = 'Failed to insert grade for homework type ID: ' . $grade_item['homework_type_id'];
                    }
                }
            }

            // Check if there were any errors
            if (!empty($errors)) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Some grades could not be saved: ' . implode(', ', $errors),
                    'errors' => $errors
                ];
            }

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Module grades saved successfully',
                'inserted_grades' => $inserted_grades,
                'count' => count($inserted_grades)
            ];

        }
 catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Database error in addGradeHomeworkModule: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        }
 catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}

?>