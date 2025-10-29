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
    <title>تنظيم أيام الدراسة - نظام إدارة الروضة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/admin-index.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../../assets/css/organize-attendance.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../../assets/css/compatibility.css?v=<?php echo time(); ?>">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>إدارة الروضة</h2>
        </div>
        <nav class="sidebar-menu">
            <ul>
                <li><a href="../index.php"><i class="fas fa-tachometer-alt"></i> <span>الصفحة الرئيسية</span></a></li>
                
               
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="البحث عن طلاب، معلمين...">
            </div>
            <div class="user-info">
                <img src="https://ui-avatars.com/api/?name=Admin+User&background=4e73df&color=fff" alt="Admin User">
                <span>الإدارة</span>
            </div>
        </div>

        <!-- Page Content -->
        <div class="dashboard">
            <h1 class="page-title">تنظيم أيام الدراسة</h1>
            <p class="page-description">قم بإعداد أيام الدراسة والعطلات لكل فصل دراسي. الأيام غير المحددة = أيام دراسية (افتراضي)، الأيام المحددة = عطلات/أيام مغلقة.</p>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- Semester Selection -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-graduation-cap"></i> اختيار الفصل الدراسي</h2>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="academicYearFilter">السنة الدراسية (فلتر اختياري)</label>
                        <select id="academicYearFilter" class="form-control">
                            <option value="">جميع السنوات الدراسية</option>
                            <!-- Academic years will be populated via JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="semesterSelect">الفصل الدراسي *</label>
                        <select id="semesterSelect" class="form-control" required>
                            <option value="">اختر فصلاً دراسياً...</option>
                            <!-- Semesters will be populated via JavaScript -->
                        </select>
                    </div>
                </div>
            </div>

            <!-- Weekly Holiday Settings -->
            <div class="card" id="weeklyHolidayCard" style="display: none;">
                <div class="card-header">
                    <h2><i class="fas fa-calendar-times"></i> العطلات الأسبوعية المتكررة</h2>
                </div>
                <div class="card-body">
                    <p class="form-help">اختر الأيام التي تكون دائماً عطلات (مثل: الجمعة، السبت). سيتم وضع علامة تلقائية على هذه الأيام كأيام مغلقة في التقويم أدناه.</p>
                    <div class="weekly-holiday-selector">
                        <div class="day-checkbox">
                            <input type="checkbox" id="sunday" value="0">
                            <label for="sunday">الأحد</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="monday" value="1">
                            <label for="monday">الاثنين</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="tuesday" value="2">
                            <label for="tuesday">الثلاثاء</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="wednesday" value="3">
                            <label for="wednesday">الأربعاء</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="thursday" value="4">
                            <label for="thursday">الخميس</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="friday" value="5">
                            <label for="friday">الجمعة</label>
                        </div>
                        <div class="day-checkbox">
                            <input type="checkbox" id="saturday" value="6">
                            <label for="saturday">السبت</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Section -->
            <div class="card" id="calendarCard" style="display: none;">
                <div class="card-header">
                    <h2><i class="fas fa-calendar-alt"></i> تقويم أيام الدراسة</h2>
                    <div class="card-actions">
                        <button class="btn btn-refresh" onclick="generateCalendar()">
                            <i class="fas fa-sync-alt"></i> تحديث التقويم
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="calendar-legend">
                        <div class="legend-item">
                            <div class="legend-color school-day"></div>
                            <span>يوم دراسي (غير محدد)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color holiday-day"></div>
                            <span>عطلة/مغلق (محدد)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color recurring-holiday"></div>
                            <span>عطلة متكررة (محدد تلقائياً)</span>
                        </div>
                    </div>
                    
                    <div id="calendarContainer" class="calendar-container">
                        <!-- Calendar will be generated here via JavaScript -->
                    </div>
                    
                    <div class="calendar-actions">
                        <button type="button" class="btn btn-primary" id="saveSchoolDaysBtn" onclick="saveSchoolDays()">
                            <i class="fas fa-save"></i>
                            <span id="saveText">حفظ أيام الدراسة</span>
                            <div class="loading-spinner" id="saveLoading" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div id="loadingContainer" class="loading-container" style="display: none;">
                <div class="loading-spinner"></div>
                <p>جاري تحميل البيانات...</p>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <i class="fas fa-calendar-alt"></i>
                <h3>لم يتم اختيار فصل دراسي</h3>
                <p>يرجى اختيار فصل دراسي لتنظيم أيام الدراسة.</p>
            </div>
        </div>
    </div>

    <!-- Add Academic Year Modal -->
    <div id="addAcademicYearModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-calendar-alt"></i> إضافة سنة دراسية</h2>
                <span class="close" onclick="closeAddAcademicYearModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addAcademicYearForm">
                    <div class="form-group">
                        <label for="yearName">اسم السنة الدراسية *</label>
                        <input type="text" id="yearName" name="yearName" class="form-control" placeholder="مثال: 2024-2025" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="academicStartDate">تاريخ البداية *</label>
                        <input type="date" id="academicStartDate" name="startDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="academicEndDate">تاريخ النهاية *</label>
                        <input type="date" id="academicEndDate" name="endDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="academicIsCurrent" name="isCurrent">
                            <span class="checkmark"></span>
                            تعيين كسنة دراسية حالية
                        </label>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeAddAcademicYearModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="submitAcademicYearBtn">
                            <span id="academicYearSubmitText">إضافة السنة الدراسية</span>
                            <div class="loading" id="academicYearLoading" style="display: none;"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Semester Modal -->
    <div id="addSemesterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-graduation-cap"></i> إضافة فصل دراسي</h2>
                <span class="close" onclick="closeAddSemesterModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addSemesterForm">
                    <div class="form-group">
                        <label for="academicYearSelect">السنة الدراسية *</label>
                        <select id="academicYearSelect" name="academicYearId" class="form-control" required>
                            <option value="">اختر سنة دراسية...</option>
                            <!-- Academic years will be populated via JavaScript -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="termName">اسم الفصل الدراسي *</label>
                        <input type="text" id="termName" name="termName" class="form-control" placeholder="مثال: خريف 2024، ربيع 2025" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="semesterStartDate">تاريخ البداية *</label>
                        <input type="date" id="semesterStartDate" name="startDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="semesterEndDate">تاريخ النهاية *</label>
                        <input type="date" id="semesterEndDate" name="endDate" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="semesterIsCurrent" name="isCurrent">
                            <span class="checkmark"></span>
                            تعيين كفصل دراسي حالي
                        </label>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeAddSemesterModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="submitSemesterBtn">
                            <span id="semesterSubmitText">إضافة الفصل الدراسي</span>
                            <div class="loading" id="semesterLoading" style="display: none;"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../../assets/js/organize-attendance.js?v=<?php echo time(); ?>"></script>
    <script src="../../../assets/js/arabic-converter.js"></script>
</body>
</html>
