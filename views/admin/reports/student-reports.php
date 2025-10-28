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
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقارير الطلاب - نظام إدارة الروضة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/student-reports.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <a href="../students/view-edit-student.php" class="back-button no-print">
                    <i class="fas fa-arrow-left"></i> العودة إلى الطلاب
                </a>
                <div class="student-info" id="studentInfo">
                    <h1><i class="fas fa-chart-line"></i> تقارير الطالب</h1>
                    <div class="student-details">
                        <h2 id="studentName">جاري التحميل...</h2>
                        <p id="studentLevel">جاري التحميل...</p>
                    </div>
                </div>
            </div>
            <div class="header-right">
                <button onclick="window.print()" class="print-button no-print">
                    <i class="fas fa-print"></i> طباعة التقرير
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
                    <i class="fas fa-calendar-alt"></i> شهري
                </button>
                <button class="toggle-btn" id="weeklyBtn" onclick="reportManager.switchPeriod('weekly')">
                    <i class="fas fa-calendar-week"></i> أسبوعي
                </button>
            </div>
            <div class="period-info" id="periodInfo">
                <span id="currentPeriod">الشهر الحالي</span>
                <span id="dateRange">جاري التحميل...</span>
            </div>
        </div>
        
        <!-- Print Period Info (shown only when printing) -->
        <div class="print-period-info">
            <p><strong>الفترة:</strong> <span id="printCurrentPeriod">شهري</span> (<span id="printDateRange">جاري التحميل...</span>)</p>
        </div>
        
        <!-- Performance Overview Cards -->
        <div class="performance-overview">
            <div class="overview-card overall-performance" id="overallPerformanceCard">
                <div class="card-header">
                    <h3><i class="fas fa-trophy"></i> الأداء العام</h3>
                </div>
                <div class="card-content">
                    <div class="performance-grade" id="overallGrade">--</div>
                    <div class="performance-level" id="overallLevel">غير متاح</div>
                    <div class="performance-details" id="overallDetails">لا توجد بيانات متاحة</div>
                </div>
            </div>
            
            <div class="overview-card attendance-performance" id="attendancePerformanceCard">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-check"></i> الحضور</h3>
                </div>
                <div class="card-content">
                    <div class="attendance-percentage" id="attendancePercentage">--%</div>
                    <div class="attendance-details" id="attendanceDetails">لا توجد بيانات متاحة</div>
                </div>
            </div>
            
            <div class="overview-card recitation-performance" id="recitationPerformanceCard">
                <div class="card-header">
                    <h3><i class="fas fa-book"></i> تلاوة القرآن</h3>
                </div>
                <div class="card-content">
                    <div class="recitation-pages" id="recitationPages">--</div>
                    <div class="recitation-details" id="recitationDetails">لا توجد بيانات متاحة</div>
                </div>
            </div>
        </div>
        
        <!-- Recitation Quantity Per Type Section -->
        <div class="recitation-types-section">
            <div class="section-header">
                <h2><i class="fas fa-quran"></i> تلاوة القرآن حسب النوع</h2>
                <div class="section-info">
                    الكمية المحفوظة حسب نوع الواجب
                </div>
            </div>
            
            <!-- Loading State -->
            <div id="recitationTypesLoading" class="loading-container">
                <div class="loading-spinner"></div>
                <p>جاري تحميل بيانات التلاوة...</p>
            </div>
            
            <!-- Types Grid -->
            <div id="recitationTypesGrid" class="recitation-types-grid" style="display: none;">
                <!-- Type cards will be dynamically generated here -->
            </div>
            
            <!-- Empty State -->
            <div id="recitationTypesEmptyState" class="empty-state" style="display: none;">
                <i class="fas fa-book-open"></i>
                <h3>لا توجد بيانات تلاوة متاحة</h3>
                <p>لم يتم العثور على واجبات قرآنية لهذه الفترة.</p>
            </div>
        </div>
        
        <!-- Modules Performance Section -->
        <div class="modules-section">
            <div class="section-header">
                <h2><i class="fas fa-book"></i> أداء الوحدات</h2>
                <div class="section-info" id="modulesInfo">
                    الأداء حسب وحدة الواجب
                </div>
            </div>
            
            <!-- Loading State -->
            <div id="modulesLoading" class="loading-container">
                <div class="loading-spinner"></div>
                <p>جاري تحميل أداء الوحدات...</p>
            </div>
            
            <!-- Modules Grid -->
            <div id="modulesGrid" class="modules-grid" style="display: none;">
                <!-- Module cards will be dynamically generated here -->
            </div>
            
            <!-- Empty State -->
            <div id="modulesEmptyState" class="empty-state" style="display: none;">
                <i class="fas fa-book-open"></i>
                <h3>لا توجد بيانات وحدات متاحة</h3>
                <p id="emptyStateMessage">لم يتم العثور على درجات واجبات لهذه الفترة.</p>
            </div>
        </div>
    </div>

    <script src="../../../assets/js/student-reports.js?v=<?php echo time(); ?>"></script>
    <script src="../../../assets/js/arabic-converter.js"></script>
</body>
</html>
