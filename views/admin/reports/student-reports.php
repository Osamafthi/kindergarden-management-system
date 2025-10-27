<?php
// Start session and include authentication
session_start();
require_once '../../../includes/autoload.php';
require_once '../../../includes/SessionManager.php';

// Initialize database and session manager
$database = new Database();
$sessionManager = new SessionManager($database);

// Check if user is logged in as admin
if (!User::isLoggedIn() || !User::isAdmin()) {
    // Redirect to login page
    header('Location: ../../auth/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Reports - Kindergarten Admin System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/student-reports.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <a href="../students/view-edit-student.php" class="back-button no-print">
                    <i class="fas fa-arrow-left"></i> Back to Students
                </a>
                <div class="student-info" id="studentInfo">
                    <h1><i class="fas fa-chart-line"></i> Student Reports</h1>
                    <div class="student-details">
                        <h2 id="studentName">Loading...</h2>
                        <p id="studentLevel">Loading...</p>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <button onclick="window.print()" class="print-button no-print">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <div class="student-photo" id="studentPhoto">
                    <div class="photo-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <div id="alertContainer" class="no-print"></div>
        
        <!-- Period Toggle -->
        <div class="period-toggle no-print">
            <div class="toggle-container">
                <button class="toggle-btn active" id="monthlyBtn" onclick="reportManager.switchPeriod('monthly')">
                    <i class="fas fa-calendar-alt"></i> Monthly
                </button>
                <button class="toggle-btn" id="weeklyBtn" onclick="reportManager.switchPeriod('weekly')">
                    <i class="fas fa-calendar-week"></i> Weekly
                </button>
            </div>
            <div class="period-info" id="periodInfo">
                <span id="currentPeriod">Current Month</span>
                <span id="dateRange">Loading...</span>
            </div>
        </div>
        
        <!-- Print Period Info (shown only when printing) -->
        <div class="print-period-info">
            <p><strong>Period:</strong> <span id="printCurrentPeriod">Monthly</span> (<span id="printDateRange">Loading...</span>)</p>
        </div>
        
        <!-- Performance Overview Cards -->
        <div class="performance-overview">
            <div class="overview-card overall-performance" id="overallPerformanceCard">
                <div class="card-header">
                    <h3><i class="fas fa-trophy"></i> Overall Performance</h3>
                </div>
                <div class="card-content">
                    <div class="performance-grade" id="overallGrade">--</div>
                    <div class="performance-level" id="overallLevel">N/A</div>
                    <div class="performance-details" id="overallDetails">No data available</div>
                </div>
            </div>
            
            <div class="overview-card attendance-performance" id="attendancePerformanceCard">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-check"></i> Attendance</h3>
                </div>
                <div class="card-content">
                    <div class="attendance-percentage" id="attendancePercentage">--%</div>
                    <div class="attendance-details" id="attendanceDetails">No data available</div>
                </div>
            </div>
            
            <div class="overview-card recitation-performance" id="recitationPerformanceCard">
                <div class="card-header">
                    <h3><i class="fas fa-book"></i> Quran Recitation</h3>
                </div>
                <div class="card-content">
                    <div class="recitation-pages" id="recitationPages">--</div>
                    <div class="recitation-details" id="recitationDetails">No data available</div>
                </div>
            </div>
        </div>
        
        <!-- Recitation Quantity Per Type Section -->
        <div class="recitation-types-section">
            <div class="section-header">
                <h2><i class="fas fa-quran"></i> Quran Recitation by Type</h2>
                <div class="section-info">
                    Quantity memorized by homework type
                </div>
            </div>
            
            <!-- Loading State -->
            <div id="recitationTypesLoading" class="loading-container">
                <div class="loading-spinner"></div>
                <p>Loading recitation data...</p>
            </div>
            
            <!-- Types Grid -->
            <div id="recitationTypesGrid" class="recitation-types-grid" style="display: none;">
                <!-- Type cards will be dynamically generated here -->
            </div>
            
            <!-- Empty State -->
            <div id="recitationTypesEmptyState" class="empty-state" style="display: none;">
                <i class="fas fa-book-open"></i>
                <h3>No Recitation Data Available</h3>
                <p>No Quran homework found for this period.</p>
            </div>
        </div>
        
        <!-- Modules Performance Section -->
        <div class="modules-section">
            <div class="section-header">
                <h2><i class="fas fa-book"></i> Module Performance</h2>
                <div class="section-info" id="modulesInfo">
                    Performance by homework module
                </div>
            </div>
            
            <!-- Loading State -->
            <div id="modulesLoading" class="loading-container">
                <div class="loading-spinner"></div>
                <p>Loading module performance...</p>
            </div>
            
            <!-- Modules Grid -->
            <div id="modulesGrid" class="modules-grid" style="display: none;">
                <!-- Module cards will be dynamically generated here -->
            </div>
            
            <!-- Empty State -->
            <div id="modulesEmptyState" class="empty-state" style="display: none;">
                <i class="fas fa-book-open"></i>
                <h3>No Module Data Available</h3>
                <p id="emptyStateMessage">No homework grades found for this period.</p>
            </div>
        </div>
    </div>

    <script src="../../../assets/js/student-reports.js"></script>
    <script src="../../../assets/js/arabic-converter.js"></script>
</body>
</html>
