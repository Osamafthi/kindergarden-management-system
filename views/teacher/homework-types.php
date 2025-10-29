<?php
// Start session and check authentication
require_once '../../includes/autoload.php';
require_once '../../includes/SessionManager.php';

$database = new Database();
$sessionManager = new SessionManager($database);

// Check if user is logged in and is a teacher
if (!User::isLoggedIn() || !User::isTeacher()) {
    header('Location: ../../views/auth/login.php');
    exit();
}

// Get session_name, session_date, classroom_id, and student_id from URL parameters
$session_name = isset($_GET['session_name']) ? $_GET['session_name'] : null;
$session_date = isset($_GET['session_date']) ? $_GET['session_date'] : null;
$classroom_id = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : null;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;

if (!$session_name || !$session_date || !$classroom_id || !$student_id) {
    header('Location: sessions.php?classroom_id=' . $classroom_id);
    exit();
}

// Get classroom information
$classroom = new Classroom($database->connect());
$classroom_result = $classroom->get_classroom_by_id($classroom_id);

if (!$classroom_result['success']) {
    header('Location: sessions.php?classroom_id=' . $classroom_id);
    exit();
}

$classroom_info = $classroom_result['data'];

// Get student information
$student = new Student($database->connect());
$student_result = $student->getStudentById($student_id);

if (!$student_result['success']) {
    header('Location: sessions.php?classroom_id=' . $classroom_id);
    exit();
}

$student_info = $student_result['data'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أنواع الواجبات - نظام إدارة الروضة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/teacher-homework-types.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/compatibility.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <button class="back-btn" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> رجوع
                </button>
                <div class="header-info">
                    <h1><i class="fas fa-book"></i> تقييم الواجبات</h1>
                    <p class="classroom-info">
                        <span class="classroom-name"><?php echo htmlspecialchars($classroom_info['name']); ?></span>
                        <span class="classroom-details">
                            الطالب: <?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?> • 
                            الجلسة: <?php echo htmlspecialchars($session_name); ?> • 
                            التاريخ: <?php echo htmlspecialchars($session_date); ?>
                        </span>
                    </p>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars(User::getCurrentUserName()); ?></span>
                        <span class="user-role">معلم</span>
                    </div>
                </div>
                <button class="logout-btn" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        <div id="alertContainer"></div>

        <!-- Student Selector Section -->
        <div class="student-selector-container">
            <div class="student-selector-header">
                <h3><i class="fas fa-users"></i> اختر طالباً</h3>
                <div class="student-count" id="studentCount">
                    <!-- Student count will be loaded here -->
                </div>
            </div>
            
            <div class="student-selector-wrapper">
                <div class="student-dropdown-container">
                    <select id="studentSelector" class="student-selector" onchange="switchStudent()">
                        <option value="">جاري تحميل الطلاب...</option>
                    </select>
                    <div class="dropdown-arrow">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                
                <!-- Mobile-friendly student cards -->
                <div class="student-cards-container" id="studentCardsContainer">
                    <!-- Student cards will be loaded here for mobile -->
                </div>
            </div>
            
            <div class="current-student-info" id="currentStudentInfo">
                <!-- Current student info will be displayed here -->
            </div>
        </div>

        <!-- Homework Types Table Section -->
        <div class="homework-container">
            <div class="section-header">
                <h2><i class="fas fa-table"></i> بيانات الواجبات</h2>
                <div class="section-actions">
                    <button class="btn btn-refresh" onclick="loadHomeworkData()">
                        <i class="fas fa-sync-alt"></i> تحديث
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingContainer" class="loading-container" style="display: none;">
                <div class="loading-spinner-large"></div>
                <p>جاري تحميل بيانات الواجبات...</p>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <i class="fas fa-book-open"></i>
                <h3>لا توجد بيانات واجبات</h3>
                <p>لم يتم إدخال بيانات واجبات لهذه الجلسة بعد.</p>
            </div>

            <!-- Quran Homework Data Table -->
            <div class="table-container">
                <table class="homework-table" id="homeworkTable">
                    <thead>
                        <tr>
                            <th class="homework-info-header">نوع الواجب</th>
                            <th class="chapter-header">اسم السورة</th>
                            <th class="range-header">نطاق الآيات</th>
                            <th class="grade-header">الدرجة</th>
                            <th class="date-header">تاريخ الإنشاء</th>
                        </tr>
                    </thead>
                    <tbody id="homeworkTableBody">
                        <!-- Quran homework data will be loaded here via JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Modules Homework Data List -->
            <div class="modules-container">
                <!-- Modules homework data will be loaded here via JavaScript -->
            </div>
            
            <!-- Submit Grades Button -->
            <div class="submit-grades-container" id="submitGradesContainer">
                <button class="btn btn-primary btn-submit-grades" id="submitGradesBtn" onclick="submitGrades()">
                    <i class="fas fa-save"></i>
                    <span id="submitGradesText">حفظ الدرجات</span>
                    <div class="loading-spinner" id="submitLoadingSpinner" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Global variables
        const sessionName = '<?php echo addslashes($session_name); ?>';
        const sessionDate = '<?php echo addslashes($session_date); ?>';
        const classroomId = <?php echo $classroom_id; ?>;
        const studentId = <?php echo $student_id; ?>;
        const teacherId = <?php echo User::getCurrentUserId(); ?>;
    </script>
    <script src="../../assets/js/teacher-homework-types.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/arabic-converter.js"></script>
</body>
</html>
