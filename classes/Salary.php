<?php
require_once 'Database.php';

class Salary {
    private $db;
    
    // Constructor
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get teacher attendance for a specific month
     * Returns array of dates when teacher attended (has records in session_homework)
     */
    public function getTeacherAttendanceForMonth($teacher_id, $month) {
        try {
            $sql = "SELECT DISTINCT DATE(created_at) as attendance_date
                    FROM session_homework 
                    WHERE teacher_id = :teacher_id 
                    AND DATE_FORMAT(created_at, '%Y-%m') = :month
                    ORDER BY attendance_date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $stmt->bindParam(':month', $month, PDO::PARAM_STR);
            $stmt->execute();
            
            $attendance_days = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return [
                'success' => true,
                'attendance_days' => $attendance_days,
                'count' => count($attendance_days)
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getTeacherAttendanceForMonth: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get school days for a specific month
     * Returns working days (is_school_day=1) and off days (is_school_day=0)
     */
    public function getSchoolDaysForMonth($month) {
        try {
            $sql = "SELECT date, is_school_day
                    FROM school_days 
                    WHERE DATE_FORMAT(date, '%Y-%m') = :month
                    ORDER BY date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':month', $month, PDO::PARAM_STR);
            $stmt->execute();
            
            $school_days = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $working_days = [];
            $off_days = [];
            
            foreach ($school_days as $day) {
                if ($day['is_school_day'] == 1) {
                    $working_days[] = $day['date'];
                } else {
                    $off_days[] = $day['date'];
                }
            }
            
            return [
                'success' => true,
                'working_days' => $working_days,
                'off_days' => $off_days,
                'total_working_days' => count($working_days),
                'total_off_days' => count($off_days),
                'all_school_days' => $school_days
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getSchoolDaysForMonth: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Calculate monthly salary based on attendance
     * Formula: (monthly_salary / 30) * (30 - missed_working_days)
     */
    public function calculateMonthlySalary($teacher_id, $month) {
        try {
            // Get teacher's monthly salary
            $teacher_sql = "SELECT monthly_salary FROM teachers WHERE id = :teacher_id";
            $teacher_stmt = $this->db->prepare($teacher_sql);
            $teacher_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $teacher_stmt->execute();
            
            $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$teacher) {
                return ['success' => false, 'message' => 'Teacher not found'];
            }
            
            $monthly_salary = $teacher['monthly_salary'];
            
            // Get attendance and school days
            $attendance_result = $this->getTeacherAttendanceForMonth($teacher_id, $month);
            $school_days_result = $this->getSchoolDaysForMonth($month);
            
            if (!$attendance_result['success'] || !$school_days_result['success']) {
                return ['success' => false, 'message' => 'Failed to get attendance or school days data'];
            }
            
            $attendance_days = $attendance_result['attendance_days'];
            $working_days = $school_days_result['working_days'];
            
            // Calculate missed working days
            $missed_working_days = [];
            foreach ($working_days as $working_day) {
                if (!in_array($working_day, $attendance_days)) {
                    $missed_working_days[] = $working_day;
                }
            }
            
            // Calculate daily salary (assuming 30 days per month)
            $daily_salary = $monthly_salary / 30;
            
            // Calculate final salary
            $missed_days_count = count($missed_working_days);
            $calculated_salary = $monthly_salary - ($daily_salary * $missed_days_count);
            
            return [
                'success' => true,
                'monthly_salary' => $monthly_salary,
                'daily_salary' => $daily_salary,
                'working_days_count' => count($working_days),
                'attended_days_count' => count($attendance_days),
                'missed_working_days_count' => $missed_days_count,
                'missed_working_days' => $missed_working_days,
                'attendance_days' => $attendance_days,
                'calculated_salary' => round($calculated_salary, 2)
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in calculateMonthlySalary: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get payment status for a teacher in a specific month
     */
    public function getPaymentStatus($teacher_id, $month) {
        try {
            $sql = "SELECT * FROM teacher_payments 
                    WHERE teacher_id = :teacher_id AND month = :month";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $stmt->bindParam(':month', $month, PDO::PARAM_STR);
            $stmt->execute();
            
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'payment' => $payment,
                'is_paid' => $payment ? (bool)$payment['is_paid'] : false
            ];
            
        } catch (PDOException $e) {
            error_log("Database error in getPaymentStatus: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Mark teacher salary as paid for a specific month
     */
    public function markAsPaid($teacher_id, $month, $amount = null) {
        try {
            // Check if payment record already exists
            $check_sql = "SELECT id FROM teacher_payments 
                         WHERE teacher_id = :teacher_id AND month = :month";
            $check_stmt = $this->db->prepare($check_sql);
            $check_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $check_stmt->bindParam(':month', $month, PDO::PARAM_STR);
            $check_stmt->execute();
            
            $existing_payment = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_payment) {
                // Update existing record
                $sql = "UPDATE teacher_payments 
                        SET is_paid = 1, paid_date = NOW(), paid_amount = :amount, updated_at = NOW()
                        WHERE teacher_id = :teacher_id AND month = :month";
            } else {
                // Insert new record
                $sql = "INSERT INTO teacher_payments (teacher_id, month, is_paid, paid_date, paid_amount, created_at, updated_at)
                        VALUES (:teacher_id, :month, 1, NOW(), :amount, NOW(), NOW())";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $stmt->bindParam(':month', $month, PDO::PARAM_STR);
            $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'Salary marked as paid successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to mark salary as paid'
                ];
            }
            
        } catch (PDOException $e) {
            error_log("Database error in markAsPaid: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get payment history for a teacher (all months since hire date)
     */
    public function getPaymentHistory($teacher_id) {
        try {
            // Get teacher's hire date
            $teacher_sql = "SELECT date_of_hire FROM teachers WHERE id = :teacher_id";
            $teacher_stmt = $this->db->prepare($teacher_sql);
            $teacher_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $teacher_stmt->execute();
            
            $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$teacher) {
                return ['success' => false, 'message' => 'Teacher not found'];
            }
            
            $hire_date = $teacher['date_of_hire'];
            $hire_month = date('Y-m', strtotime($hire_date));
            $current_month = date('Y-m');
            
            // Generate list of months from hire date to current month
            $months = [];
            $start = new DateTime($hire_month . '-01');
            $end = new DateTime($current_month . '-01');
            
            while ($start <= $end) {
                $months[] = $start->format('Y-m');
                $start->modify('+1 month');
            }
            
            // Reverse to show newest first
            $months = array_reverse($months);
            
            $history = [];
            foreach ($months as $month) {
                // Get salary calculation for this month
                $salary_calc = $this->calculateMonthlySalary($teacher_id, $month);
                
                // Get payment status for this month
                $payment_status = $this->getPaymentStatus($teacher_id, $month);
                
                $history[] = [
                    'month' => $month,
                    'month_name' => date('F Y', strtotime($month . '-01')),
                    'salary_calculation' => $salary_calc,
                    'payment_status' => $payment_status,
                    'is_paid' => $payment_status['is_paid']
                ];
            }
            
            return [
                'success' => true,
                'history' => $history,
                'total_months' => count($history)
            ];
            
        } catch (Exception $e) {
            error_log("Error in getPaymentHistory: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get comprehensive salary data for a teacher and month
     * This combines all the above methods for easy API consumption
     */
    public function getTeacherSalaryData($teacher_id, $month = null) {
        try {
            // Default to current month if not provided
            if (!$month) {
                $month = date('Y-m');
            }
            
            // Get teacher info
            $teacher_sql = "SELECT id, full_name, monthly_salary FROM teachers WHERE id = :teacher_id";
            $teacher_stmt = $this->db->prepare($teacher_sql);
            $teacher_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
            $teacher_stmt->execute();
            
            $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$teacher) {
                return ['success' => false, 'message' => 'Teacher not found'];
            }
            
            // Get salary calculation
            $salary_calc = $this->calculateMonthlySalary($teacher_id, $month);
            if (!$salary_calc['success']) {
                return $salary_calc;
            }
            
            // Get school days info
            $school_days = $this->getSchoolDaysForMonth($month);
            if (!$school_days['success']) {
                return $school_days;
            }
            
            // Get payment status
            $payment_status = $this->getPaymentStatus($teacher_id, $month);
            if (!$payment_status['success']) {
                return $payment_status;
            }
            
            return [
                'success' => true,
                'teacher' => $teacher,
                'month' => $month,
                'month_name' => date('F Y', strtotime($month . '-01')),
                'salary_calculation' => $salary_calc,
                'school_days' => $school_days,
                'payment_status' => $payment_status
            ];
            
        } catch (Exception $e) {
            error_log("Error in getTeacherSalaryData: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>
