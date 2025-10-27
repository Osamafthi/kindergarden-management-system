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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Kindergarten Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/teacher-dashboard.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</h1>
                <p class="welcome-message">Welcome back, <span id="teacherName">Loading...</span></p>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name" id="userName">Loading...</span>
                        <span class="user-role">Teacher</span>
                    </div>
                </div>
                <button class="logout-btn" onclick="teacherDashboard.logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        <div id="alertContainer"></div>

        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="stat-number" id="totalClassrooms">0</div>
                <div class="stat-label">My Classrooms</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number" id="totalStudents">0</div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-number" id="activeAssignments">0</div>
                <div class="stat-label">Active Assignments</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number" id="yearsTeaching">0</div>
                <div class="stat-label">Years Teaching</div>
            </div>
        </div>

        <!-- Classrooms Section -->
        <div class="classrooms-container">
            <div class="section-header">
                <h2><i class="fas fa-chalkboard"></i> My Classrooms</h2>
                <div class="section-actions">
                    <button class="btn btn-refresh" onclick="teacherDashboard.loadClassrooms()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingContainer" class="loading-container" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Loading classrooms...</p>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <i class="fas fa-chalkboard"></i>
                <h3>No Classrooms Assigned</h3>
                <p>You haven't been assigned to any classrooms yet. Contact the administrator for classroom assignments.</p>
            </div>

            <!-- Classrooms Grid -->
            <div id="classroomsGrid" class="classrooms-grid">
                <!-- Classrooms will be loaded here via JavaScript -->
            </div>
        </div>

        <!-- Create Session Section -->
        <div class="create-session-container">
            <div class="section-header">
                <h2><i class="fas fa-plus-circle"></i> Create New Session</h2>
            </div>
            
            <form id="sessionForm" class="session-form">
                <div class="form-group">
                    <label for="sessionName">
                        <i class="fas fa-tag"></i> Session Name
                    </label>
                    <input 
                        type="text" 
                        id="sessionName" 
                        name="session_name" 
                        placeholder="Enter session name (e.g., Math Lesson, Reading Time, Art Activity)"
                        required
                        maxlength="100"
                    >
                    <small class="form-help">Choose a descriptive name for this session</small>
                </div>
                
                <div class="form-group">
                    <label for="classroomSelect">
                        <i class="fas fa-chalkboard"></i> Select Classroom
                    </label>
                    <select id="classroomSelect" name="classroom_id" required>
                        <option value="">Choose a classroom...</option>
                        <!-- Classrooms will be populated via JavaScript -->
                    </select>
                    <small class="form-help">Select the classroom for this session</small>
                </div>

                <!-- Homework Types Section -->
                <div class="homework-types-section" id="homeworkTypesSection" style="display: none;">
                    <h3><i class="fas fa-book"></i> Homework Types</h3>
                    <div class="homework-types-container" id="homeworkTypesContainer">
                        <!-- Homework types will be loaded here via JavaScript -->
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="createSessionBtn">
                        <i class="fas fa-plus"></i>
                        <span id="submitText">Create Session</span>
                        <div class="loading-spinner" id="loadingSpinner" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Activity Section -->
        <div class="activity-container">
            <div class="section-header">
                <h2><i class="fas fa-history"></i> Recent Activity</h2>
            </div>
            <div class="activity-list" id="activityList">
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="activity-content">
                        <p class="activity-text">Logged in to the system</p>
                        <span class="activity-time" id="loginTime">Just now</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Classroom Details Modal -->
    <div id="classroomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-chalkboard"></i> Classroom Details</h3>
                <button class="modal-close" onclick="teacherDashboard.closeClassroomModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="classroomDetails">
                    <!-- Classroom details will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-modal-primary" onclick="teacherDashboard.goToAttendance(classroomId)" id="takeAttendanceBtn" style="display: none;">
                    <i class="fas fa-calendar-check"></i> Take Attendance
                </button>
                <button type="button" class="btn-modal btn-modal-secondary" onclick="teacherDashboard.closeClassroomModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../assets/js/teacher-dashboard.js?v=<?php echo time(); ?>"></script>
    <script src="../../assets/js/arabic-converter.js"></script>
</body>
</html>
