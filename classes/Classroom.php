<?php
require_once 'Database.php';
class Classroom {
    // Private database connection
    private $db;
    
    // Class properties
    private $id;
    private $classroom_name;
    private $grade_level;
    private $capacity;
    private $room_number;
    private $academic_year_id;
    private $term_id;
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
    
    public function getClassroomName() {
        return $this->classroom_name;
    }
    
    public function getGradeLevel() {
        return $this->grade_level;
    }
    
    public function getCapacity() {
        return $this->capacity;
    }
    
    public function getRoomNumber() {
        return $this->room_number;
    }
    
    public function getCreatedAt() {
        return $this->created_at;
    }
    
    public function getUpdatedAt() {
        return $this->updated_at;
    }
    
    public function getAcademicYearId() {
        return $this->academic_year_id;
    }
    
    public function getTermId() {
        return $this->term_id;
    }
    
    // Setters
    public function setId($id) {
        $this->id = $id;
    }
    
    public function setClassroomName($classroom_name) {
        $this->classroom_name = $classroom_name;
    }
    
    public function setGradeLevel($grade_level) {
        $this->grade_level = $grade_level;
    }
    
    public function setCapacity($capacity) {
        $this->capacity = $capacity;
    }
    
    public function setRoomNumber($room_number) {
        $this->room_number = $room_number;
    }
    
    public function setCreatedAt($created_at) {
        $this->created_at = $created_at;
    }
    
    public function setUpdatedAt($updated_at) {
        $this->updated_at = $updated_at;
    }
    
    public function setAcademicYearId($academic_year_id) {
        $this->academic_year_id = $academic_year_id;
    }
    
    public function setTermId($term_id) {
        $this->term_id = $term_id;
    }
    
    public function add_classroom($classroom_data) {
        try {
            // Validate required fields
            
            if (empty($classroom_data['name']) || 
                empty($classroom_data['grade_level']) || 
                empty($classroom_data['capacity']) || 
                empty($classroom_data['room_number'])) {
                return [
                    'success' => false,
                    'message' => 'All fields are required'
                ];
            }
            
            // Validate capacity is numeric and positive
            if (!is_numeric($classroom_data['capacity']) || $classroom_data['capacity'] <= 0) {
                return [
                    'success' => false,
                    'message' => 'Capacity must be a positive number'
                ];
            }
    
            // Check if room number already exists
            $check_room_query = "SELECT id FROM classrooms WHERE room_number = :room_number";
            $check_stmt = $this->db->prepare($check_room_query);
            $check_stmt->bindParam(':room_number', $classroom_data['room_number']);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'Room number already exists'
                ];
            }
          $academic_id = 1; // Default academic year ID
          $term_id = isset($classroom_data['term_id']) ? $classroom_data['term_id'] : null;
          
            // Prepare SQL query - Updated to include term_id
            $query = "INSERT INTO classrooms (name, grade_level, capacity, academic_year_id, term_id, room_number, created_at) 
                      VALUES (:name, :grade_level, :capacity, :academic_year_id, :term_id, :room_number, NOW())";
            
            $stmt = $this->db->prepare($query);
  
            // Bind parameters - Updated to include term_id
            $stmt->bindParam(':name', $classroom_data['name']);
            $stmt->bindParam(':grade_level', $classroom_data['grade_level']);
            $stmt->bindParam(':capacity', $classroom_data['capacity']);
            $stmt->bindParam(':academic_year_id', $academic_id);
            $stmt->bindParam(':term_id', $term_id);
            $stmt->bindParam(':room_number', $classroom_data['room_number']);
    
            // Execute query
            if ($stmt->execute()) {
                $classroom_id = $this->db->lastInsertId();
    
                // Set the object properties
                $this->setId($classroom_id);
                $this->setClassroomName($classroom_data['name']);
                $this->setGradeLevel($classroom_data['grade_level']);
                $this->setCapacity($classroom_data['capacity']);
                $this->setRoomNumber($classroom_data['room_number']);
    
                return [
                    'success' => true,
                    'message' => 'Classroom added successfully',
                    'classroom_id' => $classroom_id,
                    'data' => [
                        'id' => $classroom_id,
                        'name' => $classroom_data['name'],
                        'grade_level' => $classroom_data['grade_level'],
                        'capacity' => $classroom_data['capacity'],
                        'room_number' => $classroom_data['room_number']
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to add classroom'
                ];
            }
    
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Get classroom by ID
    public function get_classroom_by_id($id) {
        try {
            $query = "SELECT * FROM classrooms WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Set object properties
                $this->setId($row['id']);
                $this->setClassroomName($row['name']);
                $this->setGradeLevel($row['grade_level']);
                $this->setCapacity($row['capacity']);
                $this->setRoomNumber($row['room_number']);
                $this->setAcademicYearId($row['academic_year_id']);
                $this->setTermId($row['term_id']);
                $this->setCreatedAt($row['created_at']);
                $this->setUpdatedAt($row['updated_at']);
                
                return [
                    'success' => true,
                    'data' => $row
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Classroom not found'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Get all classrooms
    public function get_all_classrooms() {
        try {
            $query = "SELECT * FROM classrooms ORDER BY name ASC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $classrooms = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $classrooms[] = $row;
            }
            
            return [
                'success' => true,
                'data' => $classrooms,
                'count' => count($classrooms)
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Update classroom
    public function update_classroom($id, $classroom_data) {
        try {
            // Check if classroom exists
            $check_query = "SELECT id FROM classrooms WHERE id = :id";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindParam(':id', $id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Classroom not found'
                ];
            }
            
            // Check if new room number already exists (excluding current classroom)
            if (!empty($classroom_data['room_number'])) {
                $room_check_query = "SELECT id FROM classrooms WHERE room_number = :room_number AND id != :id";
                $room_check_stmt = $this->db->prepare($room_check_query);
                $room_check_stmt->bindParam(':room_number', $classroom_data['room_number']);
                $room_check_stmt->bindParam(':id', $id);
                $room_check_stmt->execute();
                
                if ($room_check_stmt->rowCount() > 0) {
                    return [
                        'success' => false,
                        'message' => 'Room number already exists'
                    ];
                }
            }
            
            // Prepare update query
            $query = "UPDATE classrooms SET 
                      name = :name, 
                      grade_level = :grade_level, 
                      capacity = :capacity, 
                      room_number = :room_number, 
                      updated_at = NOW() 
                      WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $classroom_data['name']);
            $stmt->bindParam(':grade_level', $classroom_data['grade_level']);
            $stmt->bindParam(':capacity', $classroom_data['capacity']);
            $stmt->bindParam(':room_number', $classroom_data['room_number']);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Classroom updated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to update classroom'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Delete classroom
    public function delete_classroom($id) {
        try {
            // Check if classroom exists
            $check_query = "SELECT id FROM classrooms WHERE id = :id";
            $check_stmt = $this->db->prepare($check_query);
            $check_stmt->bindParam(':id', $id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Classroom not found'
                ];
            }
            
            // TODO: Check if classroom has assigned students/teachers before deleting
            // You might want to add this logic based on your database relationships
            
            $query = "DELETE FROM classrooms WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Classroom deleted successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete classroom'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Get classrooms by grade level
    public function get_classrooms_by_grade($grade_level) {
        try {
            $query = "SELECT * FROM classrooms WHERE grade_level = :grade_level ORDER BY name ASC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':grade_level', $grade_level);
            $stmt->execute();
            
            $classrooms = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $classrooms[] = $row;
            }
            
            return [
                'success' => true,
                'data' => $classrooms,
                'count' => count($classrooms)
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
}

?>