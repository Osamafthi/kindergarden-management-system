<?php
require_once 'Database.php';

class Attendance {
    private $db;
    
    // Constructor
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Get all academic years
    public function get_academic_years() {
        try {
            $sql = "SELECT * FROM academic_years ORDER BY start_date DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'academic_years' => $results,
                'count' => count($results)
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Get all semesters
    public function get_semesters($academic_year_id = null) {
        try {
            $sql = "SELECT t.*, ay.year_name 
                    FROM terms t 
                    LEFT JOIN academic_years ay ON t.academic_year_id = ay.id";
            
            if ($academic_year_id) {
                $sql .= " WHERE t.academic_year_id = :academic_year_id";
            }
            
            $sql .= " ORDER BY t.start_date DESC";
            
            $stmt = $this->db->prepare($sql);
            
            if ($academic_year_id) {
                $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'semesters' => $results,
                'count' => count($results)
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Add semester to database
    public function add_semester($data) {
        try {
            // Validate required fields
            if (empty($data['academic_year_id'])) {
                return [
                    'success' => false,
                    'message' => 'Academic year is required'
                ];
            }

            if (empty($data['term_name'])) {
                return [
                    'success' => false,
                    'message' => 'Term name is required'
                ];
            }

            if (empty($data['start_date'])) {
                return [
                    'success' => false,
                    'message' => 'Start date is required'
                ];
            }

            if (empty($data['end_date'])) {
                return [
                    'success' => false,
                    'message' => 'End date is required'
                ];
            }

            // Validate date format
            $start_date = DateTime::createFromFormat('Y-m-d', $data['start_date']);
            $end_date = DateTime::createFromFormat('Y-m-d', $data['end_date']);

            if (!$start_date || !$end_date) {
                return [
                    'success' => false,
                    'message' => 'Invalid date format. Use YYYY-MM-DD'
                ];
            }

            // Validate that end date is after start date
            if ($end_date <= $start_date) {
                return [
                    'success' => false,
                    'message' => 'End date must be after start date'
                ];
            }

            // Check if academic year exists
            $check_year_sql = "SELECT id FROM academic_years WHERE id = :academic_year_id";
            $check_year_stmt = $this->db->prepare($check_year_sql);
            $check_year_stmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
            $check_year_stmt->execute();
            
            if (!$check_year_stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Academic year not found'
                ];
            }

            // If marking as current, unmark other current semesters in the same academic year
            if (isset($data['is_current']) && $data['is_current']) {
                $unmark_sql = "UPDATE terms SET is_current = 0 WHERE academic_year_id = :academic_year_id";
                $unmark_stmt = $this->db->prepare($unmark_sql);
                $unmark_stmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
                $unmark_stmt->execute();
            }

            // Prepare SQL statement
            $sql = "INSERT INTO terms (
                        academic_year_id,
                        term_name, 
                        start_date, 
                        end_date, 
                        is_current,
                        created_at,
                        updated_at
                    ) VALUES (
                        :academic_year_id,
                        :term_name, 
                        :start_date, 
                        :end_date, 
                        :is_current,
                        NOW(),
                        NOW()
                    )";

            $stmt = $this->db->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
            $stmt->bindParam(':term_name', $data['term_name'], PDO::PARAM_STR);
            $stmt->bindParam(':start_date', $data['start_date'], PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $data['end_date'], PDO::PARAM_STR);
            $is_current = isset($data['is_current']) && $data['is_current'] ? 1 : 0;
            $stmt->bindParam(':is_current', $is_current, PDO::PARAM_INT);

            // Execute the statement
            if ($stmt->execute()) {
                $term_id = $this->db->lastInsertId();

                return [
                    'success' => true,
                    'message' => 'Semester added successfully',
                    'term_id' => $term_id,
                    'data' => [
                        'academic_year_id' => $data['academic_year_id'],
                        'term_name' => $data['term_name'],
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'is_current' => $is_current
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to add semester to database',
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
    
    // Set school days for a semester
    public function set_school_days($data) {
        try {
            // Validate required fields
            if (empty($data['term_id'])) {
                return [
                    'success' => false,
                    'message' => 'Term ID is required'
                ];
            }

            if (empty($data['academic_year_id'])) {
                return [
                    'success' => false,
                    'message' => 'Academic year ID is required'
                ];
            }

            if (empty($data['school_days']) || !is_array($data['school_days'])) {
                return [
                    'success' => false,
                    'message' => 'School days data is required'
                ];
            }

            // Check if term exists
            $check_term_sql = "SELECT id FROM terms WHERE id = :term_id";
            $check_term_stmt = $this->db->prepare($check_term_sql);
            $check_term_stmt->bindParam(':term_id', $data['term_id'], PDO::PARAM_INT);
            $check_term_stmt->execute();
            
            if (!$check_term_stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Term not found'
                ];
            }

            // Start transaction
            $this->db->beginTransaction();

            // Delete existing school days for this term
            $delete_sql = "DELETE FROM school_days WHERE term_id = :term_id";
            $delete_stmt = $this->db->prepare($delete_sql);
            $delete_stmt->bindParam(':term_id', $data['term_id'], PDO::PARAM_INT);
            $delete_stmt->execute();

            // Insert new school days
            $insert_sql = "INSERT INTO school_days (
                                academic_year_id,
                                term_id,
                                date,
                                is_school_day,
                                note,
                                created_at
                            ) VALUES (
                                :academic_year_id,
                                :term_id,
                                :date,
                                :is_school_day,
                                :note,
                                NOW()
                            )";

            $insert_stmt = $this->db->prepare($insert_sql);
            $inserted_count = 0;

            foreach ($data['school_days'] as $school_day) {
                // Validate date format
                $date = DateTime::createFromFormat('Y-m-d', $school_day['date']);
                if (!$date) {
                    $this->db->rollBack();
                    return [
                        'success' => false,
                        'message' => 'Invalid date format: ' . $school_day['date']
                    ];
                }

                $note = $school_day['note'] ?? '';
                
                $insert_stmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
                $insert_stmt->bindParam(':term_id', $data['term_id'], PDO::PARAM_INT);
                $insert_stmt->bindParam(':date', $school_day['date'], PDO::PARAM_STR);
                $insert_stmt->bindParam(':is_school_day', $school_day['is_school_day'], PDO::PARAM_INT);
                $insert_stmt->bindParam(':note', $note, PDO::PARAM_STR);

                if ($insert_stmt->execute()) {
                    $inserted_count++;
                }
            }

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'School days updated successfully',
                'inserted_count' => $inserted_count,
                'total_days' => count($data['school_days'])
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Get school days for a specific semester
    public function get_school_days($term_id) {
        try {
            $sql = "SELECT * FROM school_days WHERE term_id = :term_id ORDER BY date ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':term_id', $term_id, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'school_days' => $results,
                'count' => count($results)
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    // Set recurring weekly holiday for a semester
    public function set_recurring_weekly_holiday($term_id, $day_of_week) {
        try {
            // Check if term exists
            $check_term_sql = "SELECT id FROM terms WHERE id = :term_id";
            $check_term_stmt = $this->db->prepare($check_term_sql);
            $check_term_stmt->bindParam(':term_id', $term_id, PDO::PARAM_INT);
            $check_term_stmt->execute();
            
            if (!$check_term_stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Term not found'
                ];
            }

            // For now, we'll just return success since recurring holidays are handled in the frontend
            // In a real implementation, you might store this in a separate table
            return [
                'success' => true,
                'message' => 'Recurring weekly holiday set successfully'
            ];

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

    // Get paginated school days for a classroom with attendance status
    public function get_paginated_school_days($classroom_id, $limit = 1, $offset = 0, $specific_date = null) {
        try {
            // First get the classroom's current term
            $classroom_sql = "SELECT c.*, c.term_id, c.academic_year_id 
                              FROM classrooms c 
                              WHERE c.id = :classroom_id";
            $classroom_stmt = $this->db->prepare($classroom_sql);
            $classroom_stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            $classroom_stmt->execute();
            $classroom = $classroom_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$classroom) {
                return [
                    'success' => false,
                    'message' => 'Classroom not found'
                ];
            }
            
            if (!$classroom['term_id']) {
                return [
                    'success' => false,
                    'message' => 'No term assigned to this classroom'
                ];
            }
            
            // Build the query for school days
            $sql = "SELECT sd.*, 
                           ar.id as attendance_record_id,
                           ar.status as attendance_status,
                           ar.created_at as attendance_created_at
                    FROM school_days sd
                    LEFT JOIN attendance_records ar ON sd.id = ar.school_day_id AND ar.classroom_id = :classroom_id
                    WHERE sd.term_id = :term_id";
            
            $params = [
                ':classroom_id' => $classroom_id,
                ':term_id' => $classroom['term_id']
            ];
            
            // Add specific date filter if provided
            if ($specific_date) {
                $sql .= " AND sd.date = :specific_date";
                $params[':specific_date'] = $specific_date;
            }
            
            $sql .= " ORDER BY sd.date ASC";
            
            // Add pagination
            if ($limit > 0) {
                $sql .= " LIMIT :limit OFFSET :offset";
                $params[':limit'] = $limit;
                $params[':offset'] = $offset;
            }
            
            $stmt = $this->db->prepare($sql);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add classroom info to each result
            foreach ($results as &$result) {
                $result['classroom_id'] = $classroom_id;
                $result['academic_year_id'] = $classroom['academic_year_id'];
                $result['term_id'] = $classroom['term_id'];
                $result['has_attendance'] = !empty($result['attendance_record_id']);
            }
            
            return [
                'success' => true,
                'school_days' => $results,
                'count' => count($results),
                'classroom_info' => [
                    'classroom_id' => $classroom_id,
                    'academic_year_id' => $classroom['academic_year_id'],
                    'term_id' => $classroom['term_id']
                ]
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // Create attendance record
    public function create_attendance_record($data) {
        try {
            $sql = "INSERT INTO attendance_records (
                        classroom_id, 
                        teacher_id, 
                        school_day_id, 
                        academic_year_id, 
                        term_id, 
                        status,
                        created_at
                    ) VALUES (
                        :classroom_id, 
                        :teacher_id, 
                        :school_day_id, 
                        :academic_year_id, 
                        :term_id, 
                        'open',
                        NOW()
                    )";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':classroom_id', $data['classroom_id'], PDO::PARAM_INT);
            $stmt->bindParam(':teacher_id', $data['teacher_id'], PDO::PARAM_INT);
            $stmt->bindParam(':school_day_id', $data['school_day_id'], PDO::PARAM_INT);
            $stmt->bindParam(':academic_year_id', $data['academic_year_id'], PDO::PARAM_INT);
            $stmt->bindParam(':term_id', $data['term_id'], PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $attendance_record_id = $this->db->lastInsertId();
                return [
                    'success' => true,
                    'attendance_record_id' => $attendance_record_id,
                    'message' => 'Attendance record created successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to create attendance record'
                ];
            }
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // Save student attendance (bulk insert/update)
    public function save_student_attendance($attendance_record_id, $students_data) {
        try {
            $this->db->beginTransaction();
            
            // First, delete existing attendance for this record
            $delete_sql = "DELETE FROM attendance_students WHERE attendance_record_id = :attendance_record_id";
            $delete_stmt = $this->db->prepare($delete_sql);
            $delete_stmt->bindParam(':attendance_record_id', $attendance_record_id, PDO::PARAM_INT);
            $delete_stmt->execute();
            
            // Insert new attendance data
            $insert_sql = "INSERT INTO attendance_students (
                              attendance_record_id, 
                              student_id, 
                              status, 
                              note,
                              created_at
                          ) VALUES (
                              :attendance_record_id, 
                              :student_id, 
                              :status, 
                              :note,
                              NOW()
                          )";
            
            $insert_stmt = $this->db->prepare($insert_sql);
            
            foreach ($students_data as $student) {
                $note = $student['note'] ?? '';
                $insert_stmt->bindParam(':attendance_record_id', $attendance_record_id, PDO::PARAM_INT);
                $insert_stmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
                $insert_stmt->bindParam(':status', $student['status'], PDO::PARAM_STR);
                $insert_stmt->bindParam(':note', $note, PDO::PARAM_STR);
                $insert_stmt->execute();
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Student attendance saved successfully',
                'count' => count($students_data)
            ];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // Get attendance record with student details
    public function get_attendance_record($classroom_id, $school_day_id) {
        try {
            $sql = "SELECT ar.*, 
                           sd.date as school_date,
                           COUNT(ast.id) as student_count
                    FROM attendance_records ar
                    LEFT JOIN school_days sd ON ar.school_day_id = sd.id
                    LEFT JOIN attendance_students ast ON ar.id = ast.attendance_record_id
                    WHERE ar.classroom_id = :classroom_id 
                    AND ar.school_day_id = :school_day_id
                    GROUP BY ar.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            $stmt->bindParam(':school_day_id', $school_day_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $attendance_record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$attendance_record) {
                return [
                    'success' => false,
                    'message' => 'Attendance record not found'
                ];
            }
            
            // Get student attendance details
            $students_sql = "SELECT ast.*, s.full_name as student_name
                             FROM attendance_students ast
                             LEFT JOIN students s ON ast.student_id = s.id
                             WHERE ast.attendance_record_id = :attendance_record_id
                             ORDER BY s.full_name";
            
            $students_stmt = $this->db->prepare($students_sql);
            $students_stmt->bindParam(':attendance_record_id', $attendance_record['id'], PDO::PARAM_INT);
            $students_stmt->execute();
            $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $attendance_record['students'] = $students;
            
            return [
                'success' => true,
                'attendance_record' => $attendance_record
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    // Reopen attendance for editing
    public function reopen_attendance($attendance_record_id) {
        try {
            $sql = "UPDATE attendance_records 
                    SET status = 'open' 
                    WHERE id = :attendance_record_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':attendance_record_id', $attendance_record_id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Attendance reopened for editing'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to reopen attendance'
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
