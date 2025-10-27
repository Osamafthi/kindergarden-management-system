<?php
require_once 'Database.php';

class Reports {
    private $db;
    
    // Constructor
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Get student basic information
    public function getStudentBasicInfo($student_id) {
        try {
            $sql = "SELECT id, first_name, last_name, photo, student_level_at_enrollment, 
                           CONCAT(first_name, ' ', last_name) as full_name
                    FROM students 
                    WHERE id = :student_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
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
            error_log("Database error in getStudentBasicInfo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get active homework modules
    public function getActiveHomeworkModules() {
        try {
            $sql = "SELECT id, name, description, max_grade 
                    FROM homework_types 
                    ORDER BY name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'modules' => $modules,
                'count' => count($modules)
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getActiveHomeworkModules: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Calculate date range for period type
    public function calculateDateRange($period_type) {
        $today = new DateTime();
        
        if ($period_type === 'weekly') {
            $start_date = clone $today;
            $start_date->modify('-7 days');
            $end_date = clone $today;
        } else { // monthly
            $start_date = new DateTime($today->format('Y-m-01')); // First day of current month
            $end_date = new DateTime($today->format('Y-m-t')); // Last day of current month
        }
        
        return [
            'start_date' => $start_date->format('Y-m-d'),
            'end_date' => $end_date->format('Y-m-d')
        ];
    }
    
    // Get student module performance for specific period
    public function getStudentModulePerformance($student_id, $period_type, $start_date = null, $end_date = null) {
        try {
            // Calculate date range if not provided
            if (!$start_date || !$end_date) {
                $date_range = $this->calculateDateRange($period_type);
                $start_date = $date_range['start_date'];
                $end_date = $date_range['end_date'];
            }
            
            $sql = "SELECT 
                        sh.homework_type_id as module_id,
                        ht.name as module_name,
                        ht.max_grade,
                        sh.grade,
                        sh.id as submission_id
                    FROM session_homework sh
                    LEFT JOIN homework_types ht ON sh.homework_type_id = ht.id
                    WHERE sh.student_id = :student_id 
                    AND sh.grade IS NOT NULL
                    AND DATE(sh.updated_at) BETWEEN :start_date AND :end_date
                    ORDER BY ht.name ASC, sh.id ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
            $stmt->execute();
            
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($submissions)) {
                return [
                    'success' => true,
                    'performance' => [],
                    'count' => 0,
                    'period' => $period_type,
                    'date_range' => ['start' => $start_date, 'end' => $end_date]
                ];
            }
            
            // Group submissions by homework type and calculate normalized averages
            $module_data = [];
            $current_module_id = null;
            $module_submissions = [];
            
            foreach ($submissions as $submission) {
                $module_id = $submission['module_id'];
                
                // If we've moved to a new module, process the previous one
                if ($current_module_id !== null && $current_module_id !== $module_id) {
                    $this->addModulePerformance($module_data, $module_submissions, $current_module_id, $submissions);
                    $module_submissions = [];
                }
                
                $current_module_id = $module_id;
                $module_submissions[] = $submission;
            }
            
            // Don't forget the last module
            if (!empty($module_submissions)) {
                $this->addModulePerformance($module_data, $module_submissions, $current_module_id, $submissions);
            }
            
            return [
                'success' => true,
                'performance' => $module_data,
                'count' => count($module_data),
                'period' => $period_type,
                'date_range' => ['start' => $start_date, 'end' => $end_date]
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getStudentModulePerformance: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Helper function to add module performance data
    private function addModulePerformance(&$module_data, $module_submissions, $module_id, $all_submissions) {
        if (empty($module_submissions)) return;
        
        // Get module info from first submission
        $first_submission = $module_submissions[0];
        $module_name = $first_submission['module_name'];
        $max_grade = $first_submission['max_grade'];
        
        // Normalize all grades and calculate average
        $normalized_sum = 0;
        $count = 0;
        
        foreach ($module_submissions as $submission) {
            $normalized_grade = $this->normalizeGrade($submission['grade'], $max_grade);
            $normalized_sum += $normalized_grade;
            $count++;
        }
        
        $avg_grade = round($normalized_sum / $count, 2);
        
        // Determine performance level based on normalized 0-10 scale
        $performance_level = 'F';
        if ($avg_grade >= 9) {
            $performance_level = 'A';
        } elseif ($avg_grade >= 8) {
            $performance_level = 'B';
        } elseif ($avg_grade >= 7) {
            $performance_level = 'C';
        } elseif ($avg_grade >= 6) {
            $performance_level = 'D';
        }
        
        $module_data[] = [
            'module_id' => $module_id,
            'module_name' => $module_name,
            'max_grade' => $max_grade,
            'avg_grade' => $avg_grade,
            'performance_level' => $performance_level,
            'total_submissions' => $count
        ];
    }
    
    // Helper function to normalize grades to 0-10 scale based on max_grade
    private function normalizeGrade($grade, $max_grade) {
        if ($max_grade == 0) return 0;
        // Convert grade to 0-10 scale: normalized = (grade / max_grade) * 10
        return ($grade / $max_grade) * 10;
    }
    
    // Get student overall performance for specific period
    public function getStudentOverallPerformance($student_id, $period_type, $start_date = null, $end_date = null) {
        try {
            // Calculate date range if not provided
            if (!$start_date || !$end_date) {
                $date_range = $this->calculateDateRange($period_type);
                $start_date = $date_range['start_date'];
                $end_date = $date_range['end_date'];
            }
            
            $sql = "SELECT 
                        sh.grade,
                        ht.max_grade,
                        sh.id as submission_id
                    FROM session_homework sh
                    INNER JOIN homework_types ht ON sh.homework_type_id = ht.id
                    WHERE sh.student_id = :student_id 
                    AND sh.grade IS NOT NULL
                    AND DATE(sh.updated_at) BETWEEN :start_date AND :end_date";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
            $stmt->execute();
            
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($submissions)) {
                return [
                    'success' => true,
                    'overall_performance' => [
                        'overall_avg_grade' => 0,
                        'overall_performance_level' => 'N/A',
                        'total_submissions' => 0
                    ],
                    'period' => $period_type,
                    'date_range' => ['start' => $start_date, 'end' => $end_date],
                    'no_data' => true
                ];
            }
            
            // Normalize all grades to 0-10 scale and calculate average
            $normalized_sum = 0;
            $count = 0;
            
            foreach ($submissions as $submission) {
                $normalized_grade = $this->normalizeGrade($submission['grade'], $submission['max_grade']);
                $normalized_sum += $normalized_grade;
                $count++;
            }
            
            $overall_avg_grade = round($normalized_sum / $count, 2);
            
            // Determine performance level based on normalized 0-10 scale
            $overall_performance_level = 'F';
            if ($overall_avg_grade >= 9) {
                $overall_performance_level = 'A';
            } elseif ($overall_avg_grade >= 8) {
                $overall_performance_level = 'B';
            } elseif ($overall_avg_grade >= 7) {
                $overall_performance_level = 'C';
            } elseif ($overall_avg_grade >= 6) {
                $overall_performance_level = 'D';
            }
            
            return [
                'success' => true,
                'overall_performance' => [
                    'overall_avg_grade' => $overall_avg_grade,
                    'overall_performance_level' => $overall_performance_level,
                    'total_submissions' => $count
                ],
                'period' => $period_type,
                'date_range' => ['start' => $start_date, 'end' => $end_date]
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getStudentOverallPerformance: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get student attendance percentage for specific period
    public function getStudentAttendancePercentage($student_id, $period_type, $start_date = null, $end_date = null) {
        try {
            // Calculate date range if not provided
            if (!$start_date || !$end_date) {
                $date_range = $this->calculateDateRange($period_type);
                $start_date = $date_range['start_date'];
                $end_date = $date_range['end_date'];
            }
            
            // Get student's classroom first
            $classroom_sql = "SELECT classroom_id FROM students WHERE id = :student_id";
            $classroom_stmt = $this->db->prepare($classroom_sql);
            $classroom_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $classroom_stmt->execute();
            $student_classroom = $classroom_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student_classroom || !$student_classroom['classroom_id']) {
                return [
                    'success' => true,
                    'attendance_percentage' => 0,
                    'total_days' => 0,
                    'present_days' => 0,
                    'no_data' => true,
                    'message' => 'Student not assigned to any classroom'
                ];
            }
            
            $classroom_id = $student_classroom['classroom_id'];
            
            // Get attendance records for the student in the specified period
            $sql = "SELECT 
                        COUNT(*) as total_days,
                        SUM(CASE WHEN ast.status IN ('present', 'late') THEN 1 ELSE 0 END) as present_days
                    FROM attendance_students ast
                    INNER JOIN attendance_records ar ON ast.attendance_record_id = ar.id
                    INNER JOIN school_days sd ON ar.school_day_id = sd.id
                    WHERE ast.student_id = :student_id
                    AND ar.classroom_id = :classroom_id
                    AND sd.date BETWEEN :start_date AND :end_date
                    AND sd.is_school_day = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindParam(':classroom_id', $classroom_id, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
            $stmt->execute();
            
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($attendance && $attendance['total_days'] > 0) {
                $percentage = round(($attendance['present_days'] / $attendance['total_days']) * 100, 1);
                
                return [
                    'success' => true,
                    'attendance_percentage' => $percentage,
                    'total_days' => (int)$attendance['total_days'],
                    'present_days' => (int)$attendance['present_days'],
                    'period' => $period_type,
                    'date_range' => ['start' => $start_date, 'end' => $end_date]
                ];
            } else {
                return [
                    'success' => true,
                    'attendance_percentage' => 0,
                    'total_days' => 0,
                    'present_days' => 0,
                    'period' => $period_type,
                    'date_range' => ['start' => $start_date, 'end' => $end_date],
                    'no_data' => true,
                    'message' => 'No attendance records for this period'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in getStudentAttendancePercentage: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get student recitation quantity (pages, words, verses)
    public function getStudentRecitationQuantity($student_id, $period_type, $start_date = null, $end_date = null) {
        try {
            // Calculate date range if not provided
            if (!$start_date || !$end_date) {
                $date_range = $this->calculateDateRange($period_type);
                $start_date = $date_range['start_date'];
                $end_date = $date_range['end_date'];
            }
            
            $sql = "SELECT 
                        SUM(qv.words) AS total_words,
                        COUNT(qv.id) AS total_verses,
                        ROUND(SUM(qv.words) / 9.0, 2) AS equivalent_lines,
                        ROUND(SUM(qv.words) / 135.0, 2) AS equivalent_pages,
                        COUNT(DISTINCT hg.id) AS total_homework_items
                    FROM session_homework sh
                    INNER JOIN homework_grades hg ON sh.homework_grades_id = hg.id
                    INNER JOIN homework_types ht ON sh.homework_type_id = ht.id
                    INNER JOIN quran_verses qv ON qv.sura = hg.quran_suras_id 
                        AND qv.ayah >= hg.quran_from 
                        AND qv.ayah <= hg.quran_to
                    WHERE sh.student_id = :student_id
                    AND ht.different_types = 'quran'
                    AND DATE(sh.updated_at) BETWEEN :start_date AND :end_date
                    AND sh.grade IS NOT NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['total_homework_items'] > 0) {
                return [
                    'success' => true,
                    'total_words' => (int)($result['total_words'] ?? 0),
                    'total_verses' => (int)($result['total_verses'] ?? 0),
                    'equivalent_lines' => (float)($result['equivalent_lines'] ?? 0),
                    'equivalent_pages' => (float)($result['equivalent_pages'] ?? 0),
                    'total_homework_items' => (int)($result['total_homework_items'] ?? 0),
                    'period' => $period_type,
                    'date_range' => ['start' => $start_date, 'end' => $end_date]
                ];
            } else {
                return [
                    'success' => true,
                    'total_words' => 0,
                    'total_verses' => 0,
                    'equivalent_lines' => 0,
                    'equivalent_pages' => 0,
                    'total_homework_items' => 0,
                    'period' => $period_type,
                    'date_range' => ['start' => $start_date, 'end' => $end_date],
                    'no_data' => true
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in getStudentRecitationQuantity: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get recitation quantity per homework type (for Quran types)
    public function getStudentRecitationQuantityPerType($student_id, $period_type, $start_date = null, $end_date = null) {
        try {
            // Calculate date range if not provided
            if (!$start_date || !$end_date) {
                $date_range = $this->calculateDateRange($period_type);
                $start_date = $date_range['start_date'];
                $end_date = $date_range['end_date'];
            }
            
            $sql = "SELECT 
                        ht.id as homework_type_id,
                        ht.name as homework_type_name,
                        SUM(qv.words) AS total_words,
                        COUNT(qv.id) AS total_verses,
                        ROUND(SUM(qv.words) / 9.0, 2) AS equivalent_lines,
                        ROUND(SUM(qv.words) / 135.0, 2) AS equivalent_pages,
                        COUNT(DISTINCT hg.id) AS total_homework_items
                    FROM session_homework sh
                    INNER JOIN homework_grades hg ON sh.homework_grades_id = hg.id
                    INNER JOIN homework_types ht ON sh.homework_type_id = ht.id
                    INNER JOIN quran_verses qv ON qv.sura = hg.quran_suras_id 
                        AND qv.ayah >= hg.quran_from 
                        AND qv.ayah <= hg.quran_to
                    WHERE sh.student_id = :student_id
                    AND ht.different_types = 'quran'
                    AND DATE(sh.updated_at) BETWEEN :start_date AND :end_date
                    AND sh.grade IS NOT NULL
                    GROUP BY ht.id, ht.name
                    ORDER BY ht.name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
            $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $quantity_per_type = [];
            foreach ($results as $row) {
                $quantity_per_type[] = [
                    'homework_type_id' => (int)$row['homework_type_id'],
                    'homework_type_name' => $row['homework_type_name'],
                    'total_words' => (int)($row['total_words'] ?? 0),
                    'total_verses' => (int)($row['total_verses'] ?? 0),
                    'equivalent_lines' => (float)($row['equivalent_lines'] ?? 0),
                    'equivalent_pages' => (float)($row['equivalent_pages'] ?? 0),
                    'total_homework_items' => (int)($row['total_homework_items'] ?? 0)
                ];
            }
            
            return [
                'success' => true,
                'quantity_per_type' => $quantity_per_type,
                'count' => count($quantity_per_type),
                'period' => $period_type,
                'date_range' => ['start' => $start_date, 'end' => $end_date]
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getStudentRecitationQuantityPerType: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // Get complete student report
    public function getStudentReport($student_id, $period_type = 'monthly') {
        try {
            // Get student basic info
            $student_info = $this->getStudentBasicInfo($student_id);
            if (!$student_info['success']) {
                return $student_info;
            }
            
            // Calculate date range
            $date_range = $this->calculateDateRange($period_type);
            
            // Get all report data
            $module_performance = $this->getStudentModulePerformance($student_id, $period_type, $date_range['start_date'], $date_range['end_date']);
            $overall_performance = $this->getStudentOverallPerformance($student_id, $period_type, $date_range['start_date'], $date_range['end_date']);
            $attendance_percentage = $this->getStudentAttendancePercentage($student_id, $period_type, $date_range['start_date'], $date_range['end_date']);
            $active_modules = $this->getActiveHomeworkModules();
            $recitation_quantity = $this->getStudentRecitationQuantity($student_id, $period_type, $date_range['start_date'], $date_range['end_date']);
            $recitation_quantity_per_type = $this->getStudentRecitationQuantityPerType($student_id, $period_type, $date_range['start_date'], $date_range['end_date']);
            
            return [
                'success' => true,
                'student' => $student_info['student'],
                'module_performance' => $module_performance['performance'] ?? [],
                'overall_performance' => $overall_performance['overall_performance'] ?? [],
                'attendance_percentage' => $attendance_percentage['attendance_percentage'] ?? 0,
                'attendance_details' => $attendance_percentage,
                'recitation_quantity' => $recitation_quantity,
                'recitation_quantity_per_type' => $recitation_quantity_per_type['quantity_per_type'] ?? [],
                'active_modules' => $active_modules['modules'] ?? [],
                'period' => $period_type,
                'date_range' => $date_range,
                'has_data' => !empty($module_performance['performance']) || !empty($overall_performance['overall_performance'])
            ];
            
        } catch (Exception $e) {
            error_log("Error in getStudentReport: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error generating report: ' . $e->getMessage()];
        }
    }
}
?>
