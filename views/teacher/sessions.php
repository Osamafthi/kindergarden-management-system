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

// Get classroom_id and student_id from URL parameters
$classroom_id = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : null;
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;

if (!$classroom_id) {
    header('Location: index.php');
    exit();
}

// Get classroom information
$classroom = new Classroom($database->connect());
$classroom_result = $classroom->get_classroom_by_id($classroom_id);

if (!$classroom_result['success']) {
    header('Location: index.php');
    exit();
}

$classroom_info = $classroom_result['data'];

// Get student information if student_id is provided
$student_info = null;
if ($student_id) {
    $student = new Student($database->connect());
    $student_result = $student->getStudentById($student_id);
    
    if ($student_result['success']) {
        $student_info = $student_result['data'];
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جلسات الفصل - نظام إدارة الروضة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/teacher-sessions.css?v=<?php echo time(); ?>">
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
                    <h1><i class="fas fa-chalkboard-teacher"></i> جلسات الفصل</h1>
                    <p class="classroom-info">
                        <span class="classroom-name"><?php echo htmlspecialchars($classroom_info['name']); ?></span>
                        <span class="classroom-details">
                            <?php if ($student_info): ?>
                                الطالب: <?php echo htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']); ?> • 
                            <?php endif; ?>
                            <?php echo htmlspecialchars($classroom_info['grade_level']); ?> • 
                            غرفة <?php echo htmlspecialchars($classroom_info['room_number']); ?>
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


        <!-- Sessions List Section -->
        <div class="sessions-container">
            <div class="section-header">
                <h2><i class="fas fa-history"></i> الجلسات السابقة</h2>
                <div class="section-actions">
                    <button class="btn btn-refresh" onclick="loadSessions()">
                        <i class="fas fa-sync-alt"></i> تحديث
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingContainer" class="loading-container" style="display: none;">
                <div class="loading-spinner-large"></div>
                <p>جاري تحميل الجلسات...</p>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <i class="fas fa-calendar-plus"></i>
                <h3>لا توجد جلسات بعد</h3>
                <p>قم بإنشاء أول جلسة لهذا الفصل للبدء.</p>
            </div>

            <!-- Sessions List -->
            <div id="sessionsList" class="sessions-list">
                <!-- Sessions will be loaded here via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Global variables
        const classroomId = <?php echo $classroom_id; ?>;
        const studentId = <?php echo $student_id ?: 'null'; ?>;
        const teacherId = <?php echo User::getCurrentUserId(); ?>;
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadSessions();
        });


        // Load sessions
        async function loadSessions() {
            showLoading();
            
            try {
                const response = await fetch(`../../api/get-sessions-by-classroom.php?classroom_id=${classroomId}`, {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    renderSessions(data.sessions);
                } else {
                    showAlert('خطأ في تحميل الجلسات: ' + data.message, 'error');
                    showEmptyState();
                }
            } catch (error) {
                console.error('خطأ في تحميل الجلسات:', error);
                showAlert('خطأ في الشبكة أثناء تحميل الجلسات', 'error');
                showEmptyState();
            } finally {
                hideLoading();
            }
        }

        // Render sessions list
        function renderSessions(sessions) {
            const sessionsList = document.getElementById('sessionsList');
            
            if (sessions.length === 0) {
                showEmptyState();
                return;
            }
            
            hideEmptyState();
            
            sessionsList.innerHTML = sessions.map(session => {
                const sessionDate = new Date(session.date);
                const createdDate = new Date(session.created_at);
                
                return `
                    <div class="session-item" onclick="viewSession('${session.session_name}', '${session.date}')">
                        <div class="session-header">
                            <div class="session-name">
                                <i class="fas fa-chalkboard"></i>
                                ${session.session_name}
                            </div>
                            <div class="session-date">
                                ${formatDate(sessionDate)}
                            </div>
                        </div>
                        
                        <div class="session-details">
                            <div class="session-detail">
                                <i class="fas fa-calendar"></i>
                                <span>تاريخ الجلسة: ${formatDate(sessionDate)}</span>
                            </div>
                            <div class="session-detail">
                                <i class="fas fa-clock"></i>
                                <span>تم الإنشاء: ${formatTimeAgo(createdDate)}</span>
                            </div>
                            <div class="session-detail">
                                <i class="fas fa-users"></i>
                                <span>الطلاب: ${session.student_count} طالب</span>
                            </div>
                            <div class="session-detail">
                                <i class="fas fa-door-open"></i>
                                <span>الفصل: ${session.classroom_name || 'غير معروف'}</span>
                            </div>
                        </div>
                        
                        <div class="session-actions">
                            <div class="btn btn-view">
                                <i class="fas fa-eye"></i> عرض التفاصيل
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }


        // Show loading
        function showLoading() {
            document.getElementById('loadingContainer').style.display = 'block';
            document.getElementById('sessionsList').innerHTML = '';
        }

        // Hide loading
        function hideLoading() {
            document.getElementById('loadingContainer').style.display = 'none';
        }

        // Show empty state
        function showEmptyState() {
            document.getElementById('emptyState').style.display = 'block';
            document.getElementById('sessionsList').innerHTML = '';
        }

        // Hide empty state
        function hideEmptyState() {
            document.getElementById('emptyState').style.display = 'none';
        }

        // Show alert
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                ${message}
            `;
            alert.style.display = 'block';
            
            alertContainer.innerHTML = '';
            alertContainer.appendChild(alert);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        // Format date
        function formatDate(date) {
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        // Format time ago
        function formatTimeAgo(date) {
            const now = new Date();
            const diffInSeconds = Math.floor((now - date) / 1000);
            
            if (diffInSeconds < 60) {
                return 'الآن';
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60);
                return `منذ ${minutes} ${minutes === 1 ? 'دقيقة' : minutes === 2 ? 'دقيقتين' : minutes <= 10 ? 'دقائق' : 'دقيقة'}`;
            } else if (diffInSeconds < 86400) {
                const hours = Math.floor(diffInSeconds / 3600);
                return `منذ ${hours} ${hours === 1 ? 'ساعة' : hours === 2 ? 'ساعتين' : hours <= 10 ? 'ساعات' : 'ساعة'}`;
            } else if (diffInSeconds < 2592000) {
                const days = Math.floor(diffInSeconds / 86400);
                return `منذ ${days} ${days === 1 ? 'يوم' : days === 2 ? 'يومين' : days <= 10 ? 'أيام' : 'يوم'}`;
            } else if (diffInSeconds < 31536000) {
                const months = Math.floor(diffInSeconds / 2592000);
                return `منذ ${months} ${months === 1 ? 'شهر' : months === 2 ? 'شهرين' : months <= 10 ? 'أشهر' : 'شهر'}`;
            } else {
                const years = Math.floor(diffInSeconds / 31536000);
                return `منذ ${years} ${years === 1 ? 'سنة' : years === 2 ? 'سنتين' : years <= 10 ? 'سنوات' : 'سنة'}`;
            }
        }

        // View session details
        function viewSession(sessionName, sessionDate) {
            if (studentId && studentId !== 'null' && studentId !== null) {
                // If student is already selected, go directly to homework types page
                window.location.href = `homework-types.php?session_name=${encodeURIComponent(sessionName)}&session_date=${sessionDate}&classroom_id=${classroomId}&student_id=${studentId}`;
            } else {
                // If no student selected, show modal to select student
                showStudentSelectionModal(sessionName, sessionDate);
            }
        }
        
        // Show student selection modal
        function showStudentSelectionModal(sessionName, sessionDate) {
            // Create modal overlay
            const modalOverlay = document.createElement('div');
            modalOverlay.className = 'modal-overlay';
            modalOverlay.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><i class="fas fa-user-graduate"></i> اختر طالباً</h3>
                        <button class="modal-close" onclick="closeStudentModal()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="studentSelectModal">
                                <i class="fas fa-user"></i> اختر الطالب
                            </label>
                            <select id="studentSelectModal" name="student_id" required>
                                <option value="">اختر طالباً...</option>
                                <!-- Students will be populated here -->
                            </select>
                            <small class="form-help">اختر الطالب لتقييم واجباته</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-modal btn-modal-secondary" onclick="closeStudentModal()">
                            <i class="fas fa-times"></i> إلغاء
                        </button>
                        <button type="button" class="btn-modal btn-modal-primary" onclick="proceedToHomework('${sessionName}', '${sessionDate}')">
                            <i class="fas fa-arrow-right"></i> متابعة
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modalOverlay);
            
            // Load students for the modal
            loadStudentsForModal();
        }
        
        // Load students for modal
        async function loadStudentsForModal() {
            try {
                const response = await fetch(`../../api/get-students-by-classroom.php?classroom_id=${classroomId}`, {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const studentSelect = document.getElementById('studentSelectModal');
                    studentSelect.innerHTML = '<option value="">اختر طالباً...</option>';
                    
                    data.students.forEach(student => {
                        const option = document.createElement('option');
                        option.value = student.id;
                        option.textContent = `${student.first_name} ${student.last_name}`;
                        studentSelect.appendChild(option);
                    });
                } else {
                    showAlert('خطأ في تحميل الطلاب: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('خطأ في تحميل الطلاب:', error);
                showAlert('خطأ في الشبكة أثناء تحميل الطلاب', 'error');
            }
        }
        
        // Close student modal
        function closeStudentModal() {
            const modalOverlay = document.querySelector('.modal-overlay');
            if (modalOverlay) {
                modalOverlay.remove();
            }
        }
        
        // Proceed to homework types page
        function proceedToHomework(sessionName, sessionDate) {
            const studentId = document.getElementById('studentSelectModal').value;
            
            if (!studentId) {
                showAlert('يرجى اختيار طالب أولاً', 'error');
                return;
            }
            
            // Navigate to homework types page with session name, date, classroom ID, and student ID
            window.location.href = `homework-types.php?session_name=${encodeURIComponent(sessionName)}&session_date=${sessionDate}&classroom_id=${classroomId}&student_id=${studentId}`;
        }

        // Go back to teacher dashboard
        function goBack() {
            window.location.href = 'index.php';
        }

        // Logout
        async function logout() {
            if (confirm('هل أنت متأكد من تسجيل الخروج؟')) {
                try {
                    const response = await fetch('../../api/logout.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin'
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.href = '../../views/auth/login.php';
                    } else {
                        showAlert('فشل تسجيل الخروج: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error('خطأ في تسجيل الخروج:', error);
                    showAlert('حدث خطأ أثناء تسجيل الخروج', 'error');
                }
            }
        }
    </script>
    <script src="../../assets/js/arabic-converter.js"></script>
</body>
</html>
