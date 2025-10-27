<?php
require_once 'Database.php';

class ClassroomTeachers {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Assign classroom to teacher
    public function assign_classroom_to_teacher($teacher_id, $classroom_id) {
        try {
            // Validate input
            if (empty($teacher_id) || empty($classroom_id)) {
                return [
                    'success' => false,
                    'message' => 'Teacher ID and Classroom ID are required'
                ];
            }
            
            // Check if teacher exists
            $teacher_check = "SELECT id FROM teachers WHERE id = :teacher_id";
            $teacher_stmt = $this->db->prepare($teacher_check);
            $teacher_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $teacher_stmt->execute();
            
            if ($teacher_stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Teacher not found'
                ];
            }
            
            // Check if classroom exists
            $classroom_check = "SELECT id FROM classrooms WHERE id = :classroom_id";
            $classroom_stmt = $this->db->prepare($classroom_check);
            $classroom_stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            $classroom_stmt->execute();
            
            if ($classroom_stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Classroom not found'
                ];
            }
            
            // Check if assignment already exists
            $existing_check = "SELECT id FROM classroom_teachers WHERE teacher_id = :teacher_id AND classroom_id = :classroom_id";
            $existing_stmt = $this->db->prepare($existing_check);
            $existing_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $existing_stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            $existing_stmt->execute();
            
            if ($existing_stmt->rowCount() > 0) {
                return [
                    'success' => false,
                    'message' => 'This teacher is already assigned to this classroom'
                ];
            }
            
            // Insert the assignment
            $insert_query = "INSERT INTO classroom_teachers (teacher_id, classroom_id, assigned_date) VALUES (:teacher_id, :classroom_id, NOW())";
            $insert_stmt = $this->db->prepare($insert_query);
            $insert_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            
            if ($insert_stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Classroom assigned to teacher successfully',
                    'assignment_id' => $this->db->lastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to assign classroom to teacher'
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
    
    // Get teacher's assigned classrooms
    public function get_teacher_classrooms($teacher_id) {
        try {
            $query = "SELECT ct.*, c.name as classroom_name, c.grade_level, c.room_number, c.capacity,
                     COUNT(s.id) as student_count
                     FROM classroom_teachers ct 
                     JOIN classrooms c ON ct.classroom_id = c.id 
                     LEFT JOIN students s ON c.id = s.classroom_id AND s.status = 'active'
                     WHERE ct.teacher_id = :teacher_id 
                     GROUP BY ct.id, c.id, c.name, c.grade_level, c.room_number, c.capacity, ct.assigned_date
                     ORDER BY ct.assigned_date DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'assignments' => $assignments,
                'count' => count($assignments)
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Remove classroom assignment from teacher
    public function remove_classroom_assignment($teacher_id, $classroom_id) {
        try {
            $query = "DELETE FROM classroom_teachers WHERE teacher_id = :teacher_id AND classroom_id = :classroom_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Classroom assignment removed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to remove classroom assignment'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
}
?>
