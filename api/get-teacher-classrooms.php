<?php
// File: api/get-teacher-classrooms.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include required files
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/SessionManager.php';

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Initialize database and session manager
    $database = new Database();
    $sessionManager = new SessionManager($database);
    
    // Check if user is logged in (can be teacher or admin)
    if (!User::isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required'
        ]);
        exit();
    }
    
    // Get teacher ID - either from parameter (admin use) or from session (teacher use)
    $teacher_id = null;
    
    if (User::isAdmin() && isset($_GET['teacher_id'])) {
        // Admin requesting specific teacher's classrooms
        $teacher_id = (int)$_GET['teacher_id'];
    } elseif (User::isTeacher()) {
        // Teacher requesting their own classrooms
        $teacher_id = User::getCurrentUserId();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Access denied - teacher ID required'
        ]);
        exit();
    }
    
    if (!$teacher_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Teacher ID not found'
        ]);
        exit();
    }
    
    // Create ClassroomTeachers instance
    $classroomTeachers = new ClassroomTeachers($database->connect());
    
    // Get teacher's classrooms
    $result = $classroomTeachers->get_teacher_classrooms($teacher_id);
    
    if ($result['success']) {
        // Get additional statistics
        $stats = getTeacherStats($database->connect(), $teacher_id);
        
        echo json_encode([
            'success' => true,
            'classrooms' => $result['assignments'],
            'count' => $result['count'],
            'stats' => $stats,
            'teacher_info' => [
                'teacher_id' => $teacher_id,
                'teacher_name' => User::getCurrentUserName(),
                'teacher_email' => User::getCurrentUserEmail()
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get teacher classrooms API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching classrooms'
    ]);
}

// Helper function to get teacher statistics
function getTeacherStats($db, $teacher_id) {
    try {
        $stats = [];
        
        // Get total students in teacher's classrooms
        $students_sql = "SELECT COUNT(DISTINCT s.id) as total_students 
                        FROM students s 
                        INNER JOIN classroom_teachers ct ON s.classroom_id = ct.classroom_id 
                        WHERE ct.teacher_id = :teacher_id";
        $students_stmt = $db->prepare($students_sql);
        $students_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $students_stmt->execute();
        $students_result = $students_stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_students'] = (int)$students_result['total_students'];
        
        // Get years teaching (based on first assignment)
        $years_sql = "SELECT MIN(assigned_date) as first_assignment 
                     FROM classroom_teachers 
                     WHERE teacher_id = :teacher_id";
        $years_stmt = $db->prepare($years_sql);
        $years_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $years_stmt->execute();
        $years_result = $years_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($years_result['first_assignment']) {
            $first_assignment = new DateTime($years_result['first_assignment']);
            $now = new DateTime();
            $stats['years_teaching'] = $now->diff($first_assignment)->y;
        } else {
            $stats['years_teaching'] = 0;
        }
        
        // Get active assignments count
        $active_sql = "SELECT COUNT(*) as active_assignments 
                      FROM classroom_teachers 
                      WHERE teacher_id = :teacher_id";
        $active_stmt = $db->prepare($active_sql);
        $active_stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
        $active_stmt->execute();
        $active_result = $active_stmt->fetch(PDO::FETCH_ASSOC);
        $stats['active_assignments'] = (int)$active_result['active_assignments'];
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error getting teacher stats: " . $e->getMessage());
        return [
            'total_students' => 0,
            'years_teaching' => 0,
            'active_assignments' => 0
        ];
    }
}
