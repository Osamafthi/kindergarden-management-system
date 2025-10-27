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

// Get classroom_id from URL
$classroom_id = isset($_GET['classroom_id']) ? (int)$_GET['classroom_id'] : null;

if (!$classroom_id) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance - Kindergarten Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/student-attendance.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="attendance-container">
        <!-- Header -->
        <div class="attendance-header">
            <div class="header-left">
                <button class="back-btn" onclick="history.back()">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="header-info">
                    <h1 id="classroomName">Loading...</h1>
                    <p class="header-subtitle">Student Attendance</p>
                </div>
            </div>
            <div class="header-right">
                <div class="attendance-status" id="attendanceStatus">
                    <span class="status-indicator" id="statusIndicator"></span>
                    <span class="status-text" id="statusText">Loading...</span>
                </div>
            </div>
        </div>

        <!-- Date Navigation -->
        <div class="date-navigation">
            <div class="date-navigation-top">
                <button class="nav-btn" id="prevDayBtn" disabled>
                    <i class="fas fa-chevron-left"></i>
                    <span>Previous</span>
                </button>
                
                <div class="current-date" id="currentDate">
                    <div class="date-display" id="dateDisplay">Loading...</div>
                    <div class="date-subtitle" id="dateSubtitle">School Day</div>
                </div>
                
                <button class="nav-btn" id="nextDayBtn" disabled>
                    <span>Next</span>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <!-- Date pagination will be rendered here by JavaScript -->
        </div>

        <!-- Loading State -->
        <div id="loadingContainer" class="loading-container">
            <div class="loading-spinner"></div>
            <p>Loading attendance data...</p>
        </div>

        <!-- Students List -->
        <div class="students-container" id="studentsContainer" style="display: none;">
            <div class="students-header">
                <h3><i class="fas fa-users"></i> Students</h3>
                <div class="students-count" id="studentsCount">0 students</div>
            </div>
            
            <div class="students-list" id="studentsList">
                <!-- Students will be loaded here -->
            </div>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="empty-state" style="display: none;">
            <i class="fas fa-calendar-times"></i>
            <h3>No School Day</h3>
            <p>This is not a school day or no school days are available for this classroom.</p>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons" id="actionButtons" style="display: none;">
            <button class="btn btn-reopen" id="reopenBtn" style="display: none;">
                <i class="fas fa-edit"></i>
                <span>Reopen & Edit</span>
            </button>
            
            <button class="btn btn-submit" id="submitBtn">
                <i class="fas fa-check"></i>
                <span id="submitText">Submit Attendance</span>
                <div class="loading-spinner" id="submitLoading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </button>
        </div>

        <!-- Alert Messages -->
        <div id="alertContainer"></div>
    </div>

    <!-- Student Note Modal -->
    <div id="noteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-sticky-note"></i> Add Note</h3>
                <button class="modal-close" onclick="studentAttendance.closeNoteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="student-info" id="noteStudentInfo">
                    <!-- Student info will be populated here -->
                </div>
                <div class="form-group">
                    <label for="studentNote">Note (optional)</label>
                    <textarea 
                        id="studentNote" 
                        class="form-control" 
                        placeholder="Enter any additional notes..."
                        rows="3"
                        maxlength="255"
                    ></textarea>
                    <small class="form-help">Maximum 255 characters</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="studentAttendance.closeNoteModal()">
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveNoteBtn">
                    Save Note
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../assets/js/student-attendance.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/arabic-converter.js"></script>
</body>
</html>
