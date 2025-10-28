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
    <title>عرض وتعديل المعلمين - نظام إدارة الروضة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/view-edit-teacher.css?v=<?php echo time(); ?>">
    <style>
        /* Assign Classroom Modal Styles */
        .teacher-info-section {
            background: #f8f9fc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-right: 4px solid var(--primary);
            border-left: none;
        }
        
        .teacher-info-section h3 {
            margin: 0 0 5px 0;
            color: var(--dark);
        }
        
        .teacher-info-section p {
            margin: 0;
            color: #6c757d;
        }
        
        .classrooms-section h4 {
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .classrooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .classroom-card {
            background: white;
            border: 2px solid #e3e6f0;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .classroom-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.15);
        }
        
        .classroom-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, #224abe 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .classroom-info h5 {
            margin: 0 0 5px 0;
            color: var(--dark);
            font-weight: 600;
        }
        
        .classroom-info p {
            margin: 2px 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* Password Input Styles */
        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .password-input-container .form-control {
            padding-left: 45px;
            padding-right: 12px;
        }
        
        .password-toggle {
            position: absolute;
            left: 10px;
            right: auto;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        .password-toggle:focus {
            outline: none;
            color: var(--primary);
        }
        
        .form-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .btn-salary {
            background: #28a745;
            color: white;
        }
        
        .btn-salary:hover {
            background: #218838;
        }
        
        /* Salary Modal Styles */
        .large-modal {
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
        }
        
        .large-modal .modal-body {
            max-height: calc(90vh - 120px);
            overflow-y: auto;
            padding: 20px;
            padding-bottom: 100px; /* Extra space for sticky payment section */
        }
        
        /* Custom scrollbar for modal body */
        .large-modal .modal-body::-webkit-scrollbar {
            width: 8px;
        }
        
        .large-modal .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .large-modal .modal-body::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .large-modal .modal-body::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        .month-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fc;
            border-radius: 8px;
        }
        
        .month-navigation h3 {
            margin: 0;
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .salary-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
            border: 2px solid #e3e6f0;
            border-radius: 12px;
            padding: 25px 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #e3e6f0 0%, var(--primary) 50%, #e3e6f0 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .summary-card:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(78, 115, 223, 0.2);
        }
        
        .summary-card:hover::before {
            opacity: 1;
        }
        
        .summary-card.highlight {
            background: linear-gradient(135deg, var(--primary) 0%, #224abe 100%);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 20px rgba(78, 115, 223, 0.3);
        }
        
        .summary-card.highlight::before {
            background: linear-gradient(90deg, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0.6) 50%, rgba(255,255,255,0.3) 100%);
            opacity: 1;
        }
        
        .summary-card label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #6c757d;
        }
        
        .summary-card.highlight label {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .summary-card span {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .summary-card.highlight span {
            color: white;
        }
        
        .attendance-calendar {
            margin-bottom: 30px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
            border-radius: 12px;
            padding: 25px;
            border: 2px solid #e3e6f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .attendance-calendar h4 {
            margin-bottom: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.3rem;
            font-weight: 700;
        }
        
        .attendance-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 12px;
        }
        
        .attendance-day {
            padding: 15px 10px;
            border-radius: 10px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 700;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        
        .attendance-day::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .attendance-day:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .attendance-day:hover::before {
            opacity: 1;
        }
        
        .attendance-day.attended {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .attendance-day.attended::before {
            background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
        }
        
        .attendance-day.missed {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .attendance-day.missed::before {
            background: linear-gradient(90deg, #dc3545 0%, #c82333 100%);
        }
        
        .attendance-day.off-day {
            background: linear-gradient(135deg, #e2e3e5 0%, #d6d8db 100%);
            color: #6c757d;
            border: 2px solid #d6d8db;
        }
        
        .attendance-day.off-day::before {
            background: linear-gradient(90deg, #6c757d 0%, #5a6268 100%);
        }
        
        .payment-section {
            text-align: center;
            padding: 25px 20px;
            background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
            border-radius: 12px;
            margin-top: 30px;
            border: 2px solid #e3e6f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .payment-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary) 0%, #28a745 50%, var(--primary) 100%);
        }
        
        .payment-section button {
            padding: 15px 40px;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .payment-section button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .payment-section button:hover::before {
            left: 100%;
        }
        
        .payment-section button:not(.paid) {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: 2px solid #28a745;
        }
        
        .payment-section button:not(.paid):hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .payment-section button:not(.paid):active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(40, 167, 69, 0.3);
        }
        
        .payment-section button.paid {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            cursor: not-allowed;
            opacity: 0.8;
        }
        
        .payment-section button.paid:hover {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            transform: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .payment-section button i {
            margin-right: 0;
            margin-left: 8px;
            font-size: 1.1em;
        }
        
        /* Salary History Styles */
        .history-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .history-item {
            background: white;
            border: 2px solid #e3e6f0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .history-item:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.15);
        }
        
        .history-item.paid {
            border-color: #28a745;
            background: #f8fff9;
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .history-month {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        .history-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .history-status.paid {
            background: #d4edda;
            color: #155724;
        }
        
        .history-status.unpaid {
            background: #f8d7da;
            color: #721c24;
        }
        
        .history-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            font-size: 0.9rem;
        }
        
        .history-detail {
            display: flex;
            justify-content: space-between;
        }
        
        .history-detail label {
            color: #6c757d;
            font-weight: 600;
        }
        
        .history-detail span {
            color: var(--dark);
            font-weight: 700;
        }
    </style> 
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-chalkboard-teacher"></i> عرض وتعديل المعلمين</h1>
            <a href="../index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> العودة إلى الصفحة الرئيسية
            </a>
        </div>
        
        <!-- Alert Messages -->
        <div id="alertContainer"></div>
        
        <!-- Search Section -->
        <div class="search-section">
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="البحث عن المعلمين بالاسم أو البريد الإلكتروني أو الهاتف...">
                <button id="searchBtn" class="search-btn">
                    <i class="fas fa-search"></i> بحث
                </button>
                <button id="clearBtn" class="clear-btn">
                    <i class="fas fa-times"></i> مسح
                </button>
            </div>
        </div>
        
        <!-- Status Toggle Buttons -->
        <div class="status-toggle-container">
            <button class="status-toggle-btn active" id="activeTeachersBtn" onclick="teachersManager.toggleTeachersView(1)">
                <i class="fas fa-check-circle"></i> المعلمون النشطون
            </button>
            <button class="status-toggle-btn" id="inactiveTeachersBtn" onclick="teachersManager.toggleTeachersView(0)">
                <i class="fas fa-times-circle"></i> المعلمون غير النشطين
            </button>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number" id="totalTeachers">0</div>
                <div class="stat-label">إجمالي المعلمين</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number" id="activeTeachers">0</div>
                <div class="stat-label">المعلمون النشطون</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number" id="recentTeachers">0</div>
                <div class="stat-label">المضافون هذا الشهر</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="stat-number" id="avgSalary">$0</div>
                <div class="stat-label">متوسط الراتب</div>
            </div>
        </div>
        
        <!-- Teachers Table -->
        <div class="teachers-container">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> قائمة المعلمين</h2>
            </div>
            
            <div class="table-container">
                <table class="teachers-table">
                    <thead>
                        <tr>
                            <th>المعلم</th>
                            <th>جهة الاتصال</th>
                            <th>الوظيفة</th>
                            <th>الراتب</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="teachersTableBody">
                        <!-- Teachers will be loaded here via JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Loading State -->
            <div id="loadingContainer" class="loading-container" style="display: none;">
                <div class="loading-spinner"></div>
                <p>جاري تحميل المعلمين...</p>
            </div>
            
            <!-- Empty State -->
            <div id="emptyState" class="empty-state" style="display: none;">
                <i class="fas fa-user-slash"></i>
                <h3>لم يتم العثور على معلمين</h3>
                <p>لا يوجد معلمون يطابقون معايير البحث.</p>
            </div>
            
            <!-- Pagination -->
            <div id="paginationContainer" class="pagination-container" style="display: none;">
                <div class="pagination-info" id="paginationInfo">
                    عرض 0 إلى 0 من 0 سجل
                </div>
                <div class="pagination-wrapper">
                    <button class="pagination-nav-btn" id="firstPageBtn" onclick="teachersManager.goToPage(1)" disabled>
                        <i class="fas fa-angle-double-left"></i> الأولى
                    </button>
                    <button class="pagination-nav-btn" id="prevPageBtn" onclick="teachersManager.goToPreviousPage()" disabled>
                        <i class="fas fa-chevron-left"></i> السابقة
                    </button>
                    <div class="pagination-buttons" id="paginationButtons">
                        <!-- Pagination buttons will be generated here -->
                    </div>
                    <button class="pagination-nav-btn" id="nextPageBtn" onclick="teachersManager.goToNextPage()" disabled>
                        التالية <i class="fas fa-chevron-right"></i>
                    </button>
                    <button class="pagination-nav-btn" id="lastPageBtn" onclick="teachersManager.goToLastPage()" disabled>
                        الأخيرة <i class="fas fa-angle-double-right"></i>
                    </button>
                    <div class="pagination-jump">
                        <span>الانتقال إلى:</span>
                        <input type="number" id="jumpToPageInput" min="1" placeholder="صفحة">
                        <button onclick="teachersManager.jumpToPage()">انتقال</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Teacher Modal -->
    <div id="editTeacherModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> تعديل المعلم</h2>
                <span class="close" onclick="teachersManager.closeEditModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editTeacherForm">
                    <input type="hidden" id="editTeacherId" name="teacher_id">
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="editFullName">الاسم الكامل *</label>
                                <input type="text" id="editFullName" name="full_name" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="editPhone">رقم الهاتف *</label>
                                <input type="tel" id="editPhone" name="phone_number" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="editEmail">البريد الإلكتروني *</label>
                                <input type="email" id="editEmail" name="email" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label>الجنس *</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="editGenderMale" name="gender" value="male" required>
                                        <label for="editGenderMale">ذكر</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="editGenderFemale" name="gender" value="female">
                                        <label for="editGenderFemale">أنثى</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="editGenderOther" name="gender" value="other">
                                        <label for="editGenderOther">آخر</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="editHourlyRate">الأجر بالساعة ($) *</label>
                                <input type="number" id="editHourlyRate" name="hourly_rate" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="editMonthlySalary">الراتب الشهري ($) *</label>
                                <input type="number" id="editMonthlySalary" name="monthly_salary" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="editPassword">كلمة المرور</label>
                                <div class="password-input-container">
                                    <input type="password" id="editPassword" name="password" class="form-control" placeholder="اتركه فارغًا للحفاظ على كلمة المرور الحالية">
                                    <button type="button" class="password-toggle" onclick="teachersManager.togglePasswordVisibility('editPassword')">
                                        <i class="fas fa-eye" id="editPasswordIcon"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">اتركه فارغًا للحفاظ على كلمة المرور الحالية دون تغيير</small>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="editConfirmPassword">تأكيد كلمة المرور</label>
                                <div class="password-input-container">
                                    <input type="password" id="editConfirmPassword" name="confirm_password" class="form-control" placeholder="تأكيد كلمة المرور الجديدة">
                                    <button type="button" class="password-toggle" onclick="teachersManager.togglePasswordVisibility('editConfirmPassword')">
                                        <i class="fas fa-eye" id="editConfirmPasswordIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="teachersManager.closeEditModal()">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="updateTeacherBtn">
                            <span id="updateText">تحديث المعلم</span>
                            <div class="loading" id="updateLoading" style="display: none;"></div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Classroom Modal -->
    <div id="assignClassroomModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-school"></i> تعيين فصل دراسي</h2>
                <span class="close" onclick="teachersManager.closeAssignClassroomModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="teacher-info-section">
                    <h3 id="assignTeacherName"></h3>
                    <p id="assignTeacherDetails"></p>
                </div>
                
                <div class="classrooms-section">
                    <h4>الفصول الدراسية المتاحة</h4>
                    <div id="classroomsList" class="classrooms-grid">
                        <!-- Classrooms will be loaded here -->
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="teachersManager.closeAssignClassroomModal()">إلغاء</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Salary Modal -->
    <div id="salaryModal" class="modal">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h2><i class="fas fa-money-bill-wave"></i> تفاصيل الراتب - <span id="salaryTeacherName"></span></h2>
                <span class="close" onclick="teachersManager.closeSalaryModal()">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Month Navigation -->
                <div class="month-navigation">
                    <button class="btn btn-secondary" onclick="teachersManager.viewSalaryHistory()">
                        <i class="fas fa-history"></i> عرض الأشهر السابقة
                    </button>
                    <h3 id="currentMonth"></h3>
                </div>
                
                <!-- Salary Summary -->
                <div class="salary-summary">
                    <div class="summary-card">
                        <label>الراتب الشهري:</label>
                        <span id="monthlySalary"></span>
                    </div>
                    <div class="summary-card">
                        <label>أيام العمل:</label>
                        <span id="workingDays"></span>
                    </div>
                    <div class="summary-card">
                        <label>أيام الحضور:</label>
                        <span id="attendedDays"></span>
                    </div>
                    <div class="summary-card">
                        <label>أيام الغياب:</label>
                        <span id="missedDays"></span>
                    </div>
                    <div class="summary-card highlight">
                        <label>الراتب المحسوب:</label>
                        <span id="calculatedSalary"></span>
                    </div>
                </div>
                
                <!-- Attendance Calendar -->
                <div class="attendance-calendar">
                    <h4><i class="fas fa-calendar-check"></i> تفاصيل الحضور</h4>
                    <div id="attendanceList"></div>
                </div>
                
                <!-- Payment Button -->
                <div class="payment-section">
                    <button id="paymentBtn" class="btn btn-primary" onclick="teachersManager.markSalaryPaid()">
                        <i class="fas fa-check"></i> دفع الراتب
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Salary History Modal -->
    <div id="salaryHistoryModal" class="modal">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h2><i class="fas fa-history"></i> سجل الرواتب</h2>
                <span class="close" onclick="teachersManager.closeSalaryHistoryModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="historyList"></div>
            </div>
        </div>
    </div>

    <script src="../../../assets/js/assign_classroom_index.js"></script>
    <script src="../../../assets/js/arabic-converter.js"></script>
    <script>
        class TeachersManager {
            constructor() {
                this.teachers = [];
                this.currentPage = 1;
                this.itemsPerPage = 10;
                this.totalCount = 0;
                this.searchTerm = '';
                this.isLoading = false;
                this.currentIsActive = 1; // 1 for active, 0 for inactive
                
                this.initializeEventListeners();
                this.loadTeachers();
            }
            
            initializeEventListeners() {
                // Search functionality
                document.getElementById('searchBtn').addEventListener('click', () => {
                    this.searchTerm = document.getElementById('searchInput').value.trim();
                    this.currentPage = 1;
                    this.loadTeachers();
                });
                
                // Clear search
                document.getElementById('clearBtn').addEventListener('click', () => {
                    document.getElementById('searchInput').value = '';
                    this.searchTerm = '';
                    this.currentPage = 1;
                    this.loadTeachers();
                });
                
                // Search on Enter key
                document.getElementById('searchInput').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.searchTerm = document.getElementById('searchInput').value.trim();
                        this.currentPage = 1;
                        this.loadTeachers();
                    }
                });
                
                // Edit teacher form submission
                const editTeacherForm = document.getElementById('editTeacherForm');
                if (editTeacherForm) {
                    editTeacherForm.addEventListener('submit', this.handleEditTeacherSubmit.bind(this));
                }
                
                // Close modal when clicking outside of it
                window.addEventListener('click', (event) => {
                    const editModal = document.getElementById('editTeacherModal');
                    const assignModal = document.getElementById('assignClassroomModal');
                    const salaryModal = document.getElementById('salaryModal');
                    const salaryHistoryModal = document.getElementById('salaryHistoryModal');
                    
                    if (event.target === editModal) {
                        this.closeEditModal();
                    }
                    
                    if (event.target === assignModal) {
                        this.closeAssignClassroomModal();
                    }
                    
                    if (event.target === salaryModal) {
                        this.closeSalaryModal();
                    }
                    
                    if (event.target === salaryHistoryModal) {
                        this.closeSalaryHistoryModal();
                    }
                });
                
                // Jump to page input keyboard support
                document.getElementById('jumpToPageInput').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.jumpToPage();
                    }
                });
            }
            
            async loadTeachers() {
                if (this.isLoading) return;
                
                this.isLoading = true;
                this.showLoading();
                
                try {
                    const params = new URLSearchParams({
                        limit: this.itemsPerPage,
                        offset: (this.currentPage - 1) * this.itemsPerPage,
                        is_active: this.currentIsActive
                    });
                    
                    if (this.searchTerm) {
                        params.append('search', this.searchTerm);
                    }
                    
                    const response = await fetch(`../../../api/get-teachers.php?${params}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        this.teachers = data.teachers;
                        this.totalCount = data.total_count;
                        this.renderTeachers();
                        this.updateStats();
                        this.renderPagination();
                    } else {
                        this.showAlert('خطأ في تحميل المعلمين: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error('خطأ:', error);
                    this.showAlert('خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
                } finally {
                    this.isLoading = false;
                    this.hideLoading();
                }
            }
            
            // Toggle between active and inactive teachers
            toggleTeachersView(isActive) {
                this.currentIsActive = isActive;
                this.currentPage = 1;
                this.searchTerm = '';
                document.getElementById('searchInput').value = '';
                
                // Update button states
                const activeBtn = document.getElementById('activeTeachersBtn');
                const inactiveBtn = document.getElementById('inactiveTeachersBtn');
                
                if (isActive === 1) {
                    activeBtn.classList.add('active');
                    inactiveBtn.classList.remove('active');
                } else {
                    activeBtn.classList.remove('active');
                    inactiveBtn.classList.add('active');
                }
                
                this.loadTeachers();
            }
            
            renderTeachers() {
                const tbody = document.getElementById('teachersTableBody');
                
                if (this.teachers.length === 0) {
                    this.showEmptyState();
                    return;
                }
                
                this.hideEmptyState();
                
                tbody.innerHTML = this.teachers.map(teacher => `
                    <tr class="fade-in">
                        <td>
                            <div class="teacher-info">
                                <div class="teacher-avatar">
                                    ${teacher.full_name.charAt(0).toUpperCase()}
                                </div>
                                <div class="teacher-details">
                                    <h4>${teacher.full_name}</h4>
                                    <p>ID: ${teacher.id}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="teacher-details">
                                <p><i class="fas fa-envelope"></i> ${teacher.email}</p>
                                <p><i class="fas fa-phone"></i> ${teacher.phone_number}</p>
                            </div>
                        </td>
                        <td>
                            <div class="teacher-details">
                                <p><strong>${teacher.gender}</strong></p>
                                <p>تاريخ التعيين: ${new Date(teacher.date_of_hire).toLocaleDateString()}</p>
                            </div>
                        </td>
                        <td>
                            <div class="teacher-details">
                                <p><strong>$${parseFloat(teacher.hourly_rate).toFixed(2)}/hr</strong></p>
                                <p>$${parseFloat(teacher.monthly_salary).toFixed(2)}/month</p>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge active">Active</span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                ${this.currentIsActive === 1 ? `
                                <button class="btn btn-view" onclick="teachersManager.assignClassroom(${teacher.id})">
                                    <i class="fas fa-school"></i> تعيين فصل
                                </button>
                                <button class="btn btn-deactivate" onclick="teachersManager.deactivateTeacher(${teacher.id})">
                                    <i class="fas fa-user-slash"></i> إلغاء التفعيل
                                </button>
                                ` : `
                                <button class="btn btn-activate" onclick="teachersManager.activateTeacher(${teacher.id})">
                                    <i class="fas fa-user-check"></i> تفعيل
                                </button>
                                `}
                                <button class="btn btn-salary" onclick="teachersManager.viewSalary(${teacher.id})">
                                    <i class="fas fa-money-bill-wave"></i> عرض الراتب
                                </button>
                                <button class="btn btn-edit" onclick="teachersManager.editTeacher(${teacher.id})">
                                    <i class="fas fa-edit"></i> تعديل
                                </button>
                                <button class="btn btn-delete" onclick="teachersManager.deleteTeacher(${teacher.id})">
                                    <i class="fas fa-trash"></i> حذف
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
            
            updateStats() {
                document.getElementById('totalTeachers').textContent = this.totalCount;
                document.getElementById('activeTeachers').textContent = this.teachers.filter(t => t.role === 'teacher').length;
                
                // Calculate recent teachers (added this month)
                const thisMonth = new Date().getMonth();
                const thisYear = new Date().getFullYear();
                const recentCount = this.teachers.filter(teacher => {
                    const hireDate = new Date(teacher.date_of_hire);
                    return hireDate.getMonth() === thisMonth && hireDate.getFullYear() === thisYear;
                }).length;
                document.getElementById('recentTeachers').textContent = recentCount;
                
                // Calculate average salary
                if (this.teachers.length > 0) {
                    const avgSalary = this.teachers.reduce((sum, teacher) => sum + parseFloat(teacher.monthly_salary), 0) / this.teachers.length;
                    document.getElementById('avgSalary').textContent = '$' + avgSalary.toFixed(0);
                }
            }
            
            renderPagination() {
                const container = document.getElementById('paginationContainer');
                const info = document.getElementById('paginationInfo');
                const buttons = document.getElementById('paginationButtons');
                
                if (this.totalCount <= this.itemsPerPage) {
                    container.style.display = 'none';
                    return;
                }
                
                container.style.display = 'flex';
                
                const totalPages = Math.ceil(this.totalCount / this.itemsPerPage);
                const startItem = (this.currentPage - 1) * this.itemsPerPage + 1;
                const endItem = Math.min(this.currentPage * this.itemsPerPage, this.totalCount);
                
                info.textContent = `عرض ${startItem} إلى ${endItem} من ${this.totalCount} سجل`;
                
                // Update navigation buttons
                document.getElementById('firstPageBtn').disabled = this.currentPage === 1;
                document.getElementById('prevPageBtn').disabled = this.currentPage === 1;
                document.getElementById('nextPageBtn').disabled = this.currentPage === totalPages;
                document.getElementById('lastPageBtn').disabled = this.currentPage === totalPages;
                
                // Generate smart pagination buttons with ellipsis
                buttons.innerHTML = this.generatePaginationButtons(this.currentPage, totalPages);
                
                // Update jump to page input
                document.getElementById('jumpToPageInput').max = totalPages;
                document.getElementById('jumpToPageInput').value = '';
            }
            
            generatePaginationButtons(currentPage, totalPages) {
                const buttons = [];
                const maxVisiblePages = 7; // Show max 7 page buttons
                
                if (totalPages <= maxVisiblePages) {
                    // Show all pages if total is small
                    for (let i = 1; i <= totalPages; i++) {
                        buttons.push(this.createPageButton(i, i === currentPage));
                    }
                } else {
                    // Smart pagination with ellipsis
                    if (currentPage <= 4) {
                        // Show first 5 pages, ellipsis, last page
                        for (let i = 1; i <= 5; i++) {
                            buttons.push(this.createPageButton(i, i === currentPage));
                        }
                        buttons.push(this.createEllipsisButton());
                        buttons.push(this.createPageButton(totalPages, false));
                    } else if (currentPage >= totalPages - 3) {
                        // Show first page, ellipsis, last 5 pages
                        buttons.push(this.createPageButton(1, false));
                        buttons.push(this.createEllipsisButton());
                        for (let i = totalPages - 4; i <= totalPages; i++) {
                            buttons.push(this.createPageButton(i, i === currentPage));
                        }
                    } else {
                        // Show first page, ellipsis, current-1, current, current+1, ellipsis, last page
                        buttons.push(this.createPageButton(1, false));
                        buttons.push(this.createEllipsisButton());
                        for (let i = currentPage - 1; i <= currentPage + 1; i++) {
                            buttons.push(this.createPageButton(i, i === currentPage));
                        }
                        buttons.push(this.createEllipsisButton());
                        buttons.push(this.createPageButton(totalPages, false));
                    }
                }
                
                return buttons.join('');
            }
            
            createPageButton(pageNumber, isActive) {
                return `<button class="pagination-btn ${isActive ? 'active' : ''}" onclick="teachersManager.goToPage(${pageNumber})">${pageNumber}</button>`;
            }
            
            createEllipsisButton() {
                return `<span class="pagination-btn ellipsis">...</span>`;
            }
            
            goToPage(page) {
                if (page < 1 || page > Math.ceil(this.totalCount / this.itemsPerPage)) return;
                this.currentPage = page;
                this.loadTeachers();
            }
            
            goToPreviousPage() {
                if (this.currentPage > 1) {
                    this.goToPage(this.currentPage - 1);
                }
            }
            
            goToNextPage() {
                const totalPages = Math.ceil(this.totalCount / this.itemsPerPage);
                if (this.currentPage < totalPages) {
                    this.goToPage(this.currentPage + 1);
                }
            }
            
            goToFirstPage() {
                this.goToPage(1);
            }
            
            goToLastPage() {
                const totalPages = Math.ceil(this.totalCount / this.itemsPerPage);
                this.goToPage(totalPages);
            }
            
            jumpToPage() {
                const input = document.getElementById('jumpToPageInput');
                const page = parseInt(input.value);
                const totalPages = Math.ceil(this.totalCount / this.itemsPerPage);
                
                if (page && page >= 1 && page <= totalPages) {
                    this.goToPage(page);
                } else {
                    this.showAlert(`يرجى إدخال رقم صفحة صالح بين 1 و ${totalPages}`, 'error');
                }
            }
            
            showLoading() {
                document.getElementById('loadingContainer').style.display = 'block';
                document.getElementById('teachersTableBody').innerHTML = '';
                document.getElementById('paginationContainer').style.display = 'none';
            }
            
            hideLoading() {
                document.getElementById('loadingContainer').style.display = 'none';
            }
            
            showEmptyState() {
                document.getElementById('emptyState').style.display = 'block';
                document.getElementById('paginationContainer').style.display = 'none';
            }
            
            hideEmptyState() {
                document.getElementById('emptyState').style.display = 'none';
            }
            
            showAlert(message, type) {
                const alertContainer = document.getElementById('alertContainer');
                const alert = document.createElement('div');
                alert.className = `alert alert-${type}`;
                alert.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
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
            
            // Action methods
            assignClassroom(id) {
                // Find the teacher data
                const teacher = this.teachers.find(t => t.id == id);
                if (!teacher) {
                    this.showAlert('Teacher not found', 'error');
                    return;
                }
                
                // Set current teacher for assignment
                this.currentTeacherId = id;
                
                // Populate teacher info in modal
                document.getElementById('assignTeacherName').textContent = teacher.full_name;
                document.getElementById('assignTeacherDetails').textContent = `${teacher.email} • ${teacher.phone_number}`;
                
                // Load classrooms
                this.loadClassrooms();
                
                // Show the modal
                this.openAssignClassroomModal();
            }
            
            openAssignClassroomModal() {
                document.getElementById('assignClassroomModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
            
            closeAssignClassroomModal() {
                document.getElementById('assignClassroomModal').style.display = 'none';
                document.body.style.overflow = 'auto';
                this.currentTeacherId = null;
            }
            
            async loadClassrooms() {
                try {
                    const response = await fetch('../../../api/get-classrooms.php');
                    const data = await response.json();
                    
                    if (data.success) {
                        this.renderClassrooms(data.classrooms);
                    } else {
                        this.showAlert('خطأ في تحميل الفصول الدراسية: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error('خطأ:', error);
                    this.showAlert('خطأ في الشبكة أثناء تحميل الفصول الدراسية.', 'error');
                }
            }
            
            renderClassrooms(classrooms) {
                const container = document.getElementById('classroomsList');
                
                if (classrooms.length === 0) {
                    container.innerHTML = '<p>لا توجد فصول دراسية متاحة.</p>';
                    return;
                }
                
                container.innerHTML = classrooms.map(classroom => `
                    <div class="classroom-card" onclick="teachersManager.assignClassroomToTeacher(${classroom.id}, '${classroom.name}')">
                        <div class="classroom-icon">
                            <i class="fas fa-school"></i>
                        </div>
                        <div class="classroom-info">
                            <h5>${classroom.name}</h5>
                            <p>المستوى: ${classroom.grade_level}</p>
                            <p>الغرفة: ${classroom.room_number}</p>
                            <p>السعة: ${classroom.capacity}</p>
                        </div>
                    </div>
                `).join('');
            }
            
            async assignClassroomToTeacher(classroomId, classroomName) {
                if (!this.currentTeacherId) {
                    this.showAlert('لم يتم اختيار معلم', 'error');
                    return;
                }
                
                try {
                    const response = await fetch('../../../api/assign-classroom.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            teacher_id: this.currentTeacherId,
                            classroom_id: classroomId
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showAlert(`تم تعيين الفصل الدراسي "${classroomName}" بنجاح!`, 'success');
                        this.closeAssignClassroomModal();
                    } else {
                        this.showAlert('خطأ في تعيين الفصل الدراسي: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error('خطأ:', error);
                    this.showAlert('خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
                }
            }
            
            editTeacher(id) {
                // Find the teacher data
                const teacher = this.teachers.find(t => t.id == id);
                if (!teacher) {
                    this.showAlert('لم يتم العثور على المعلم', 'error');
                    return;
                }
                
                // Populate the edit form with current data
                this.populateEditForm(teacher);
                
                // Show the modal
                this.openEditModal();
            }
            
            populateEditForm(teacher) {
                document.getElementById('editTeacherId').value = teacher.id;
                document.getElementById('editFullName').value = teacher.full_name;
                document.getElementById('editPhone').value = teacher.phone_number;
                document.getElementById('editEmail').value = teacher.email;
                document.getElementById('editHourlyRate').value = teacher.hourly_rate;
                document.getElementById('editMonthlySalary').value = teacher.monthly_salary;
                
                // Set gender radio button
                const genderRadios = document.querySelectorAll('input[name="gender"]');
                genderRadios.forEach(radio => {
                    radio.checked = radio.value === teacher.gender;
                });
            }
            
            openEditModal() {
                document.getElementById('editTeacherModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
            
            closeEditModal() {
                document.getElementById('editTeacherModal').style.display = 'none';
                document.body.style.overflow = 'auto';
                document.getElementById('editTeacherForm').reset();
                this.setUpdateLoadingState(false);
            }
            
            togglePasswordVisibility(fieldId) {
                const passwordField = document.getElementById(fieldId);
                const icon = document.getElementById(fieldId + 'Icon');
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordField.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
            
            async handleEditTeacherSubmit(e) {
                e.preventDefault();
                
                const password = document.getElementById('editPassword').value.trim();
                const confirmPassword = document.getElementById('editConfirmPassword').value.trim();
                
                // Validate passwords if provided
                if (password || confirmPassword) {
                    if (password !== confirmPassword) {
                        this.showAlert('كلمات المرور غير متطابقة', 'error');
                        return;
                    }
                    if (password.length < 6) {
                        this.showAlert('يجب أن تكون كلمة المرور 6 أحرف على الأقل', 'error');
                        return;
                    }
                }
                
                const formData = {
                    teacher_id: parseInt(document.getElementById('editTeacherId').value),
                    full_name: document.getElementById('editFullName').value.trim(),
                    phone_number: document.getElementById('editPhone').value.trim(),
                    email: document.getElementById('editEmail').value.trim(),
                    gender: document.querySelector('input[name="gender"]:checked').value,
                    hourly_rate: parseFloat(document.getElementById('editHourlyRate').value),
                    monthly_salary: parseFloat(document.getElementById('editMonthlySalary').value)
                };
                
                // Only include password if provided
                if (password) {
                    formData.password = password;
                }
                
                // Validate form data
                if (!this.validateEditForm(formData)) {
                    return;
                }
                
                // Show loading state
                this.setUpdateLoadingState(true);
                
                try {
                    const response = await fetch('../../../api/update-teacher.php', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('تم تحديث المعلم بنجاح!', 'success');
                        this.closeEditModal();
                        // Reload teachers to show updated data
                        this.loadTeachers();
                    } else {
                        this.showAlert('خطأ في تحديث المعلم: ' + result.message, 'error');
                    }
                } catch (error) {
                    console.error('خطأ:', error);
                    this.showAlert('خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
                } finally {
                    this.setUpdateLoadingState(false);
                }
            }
            
            validateEditForm(data) {
                if (!data.full_name || data.full_name.length < 2) {
                    this.showAlert('يجب أن يكون الاسم الكامل حرفين على الأقل.', 'error');
                    return false;
                }
                
                if (!data.phone_number || data.phone_number.length < 10) {
                    this.showAlert('يجب أن يكون رقم الهاتف 10 أرقام على الأقل.', 'error');
                    return false;
                }
                
                if (!data.email || !/\S+@\S+\.\S+/.test(data.email)) {
                    this.showAlert('يرجى إدخال عنوان بريد إلكتروني صالح.', 'error');
                    return false;
                }
                
                if (!data.gender) {
                    this.showAlert('يرجى اختيار الجنس.', 'error');
                    return false;
                }
                
                if (!data.hourly_rate || data.hourly_rate < 0) {
                    this.showAlert('يجب أن يكون الأجر بالساعة رقمًا موجبًا.', 'error');
                    return false;
                }
                
                if (!data.monthly_salary || data.monthly_salary < 0) {
                    this.showAlert('يجب أن يكون الراتب الشهري رقمًا موجبًا.', 'error');
                    return false;
                }
                
                return true;
            }
            
            setUpdateLoadingState(isLoading) {
                const updateBtn = document.getElementById('updateTeacherBtn');
                const updateText = document.getElementById('updateText');
                const updateLoading = document.getElementById('updateLoading');
                
                updateBtn.disabled = isLoading;
                
                if (isLoading) {
                    updateText.textContent = 'جاري التحديث...';
                    updateLoading.style.display = 'inline-block';
                } else {
                    updateText.textContent = 'تحديث المعلم';
                    updateLoading.style.display = 'none';
                }
            }
            
            deleteTeacher(id) {
                if (confirm('هل أنت متأكد من حذف هذا المعلم?')) {
                    this.showAlert(`حذف المعلم برقم: ${id}`, 'warning');
                    // Implement delete functionality
                }
            }
            
            // De-activate teacher
            async deactivateTeacher(teacher_id) {
                if (confirm('هل أنت متأكد من إلغاء تفعيل هذا المعلم?')) {
                    try {
                        const response = await fetch('../../../api/update-teacher-status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                teacher_id: teacher_id,
                                is_active: 0
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.showAlert('تم إلغاء تفعيل المعلم بنجاح!', 'success');
                            this.loadTeachers(); // Reload the list
                        } else {
                            this.showAlert('خطأ في إلغاء تفعيل المعلم: ' + data.message, 'error');
                        }
                    } catch (error) {
                        console.error('خطأ:', error);
                        this.showAlert('خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
                    }
                }
            }
            
            // Activate teacher
            async activateTeacher(teacher_id) {
                if (confirm('هل أنت متأكد من تفعيل هذا المعلم?')) {
                    try {
                        const response = await fetch('../../../api/update-teacher-status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                teacher_id: teacher_id,
                                is_active: 1
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            this.showAlert('تم تفعيل المعلم بنجاح!', 'success');
                            this.loadTeachers(); // Reload the list
                        } else {
                            this.showAlert('خطأ في تفعيل المعلم: ' + data.message, 'error');
                        }
                    } catch (error) {
                        console.error('خطأ:', error);
                        this.showAlert('خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
                    }
                }
            }
            
            // Salary Management Methods
            viewSalary(teacherId, month = null) {
                this.currentTeacherId = teacherId;
                this.currentSalaryMonth = month || new Date().toISOString().slice(0, 7); // YYYY-MM format
                
                // Find teacher name for display
                const teacher = this.teachers.find(t => t.id == teacherId);
                if (teacher) {
                    document.getElementById('salaryTeacherName').textContent = teacher.full_name;
                }
                
                this.loadSalaryData(teacherId, this.currentSalaryMonth);
                this.openSalaryModal();
            }
            
            openSalaryModal() {
                document.getElementById('salaryModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
            
            closeSalaryModal() {
                document.getElementById('salaryModal').style.display = 'none';
                document.body.style.overflow = 'auto';
                this.currentTeacherId = null;
                this.currentSalaryMonth = null;
            }
            
            async loadSalaryData(teacherId, month) {
                try {
                    const response = await fetch(`../../../api/get-teacher-salary.php?teacher_id=${teacherId}&month=${month}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        this.renderSalaryData(data);
                    } else {
                        this.showAlert('خطأ في تحميل بيانات الراتب: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error('خطأ:', error);
                    this.showAlert('خطأ في الشبكة أثناء تحميل بيانات الراتب.', 'error');
                }
            }
            
            renderSalaryData(data) {
                const { teacher, month_name, salary_calculation, school_days, payment_status } = data;
                
                // Update month display
                document.getElementById('currentMonth').textContent = month_name;
                
                // Update summary cards
                document.getElementById('monthlySalary').textContent = '$' + parseFloat(teacher.monthly_salary).toFixed(2);
                document.getElementById('workingDays').textContent = salary_calculation.working_days_count;
                document.getElementById('attendedDays').textContent = salary_calculation.attended_days_count;
                document.getElementById('missedDays').textContent = salary_calculation.missed_working_days_count;
                document.getElementById('calculatedSalary').textContent = '$' + salary_calculation.calculated_salary.toFixed(2);
                
                // Render attendance calendar
                this.renderAttendanceCalendar(salary_calculation.attendance_days, school_days.all_school_days);
                
                // Update payment button
                this.updatePaymentButton(payment_status.is_paid, payment_status.payment);
            }
            
            renderAttendanceCalendar(attendanceDays, schoolDays) {
                const container = document.getElementById('attendanceList');
                
                // Create a map of school days for quick lookup
                const schoolDaysMap = {};
                schoolDays.forEach(day => {
                    schoolDaysMap[day.date] = day.is_school_day;
                });
                
                // Get all days in the month
                const month = this.currentSalaryMonth;
                const year = parseInt(month.split('-')[0]);
                const monthNum = parseInt(month.split('-')[1]);
                const daysInMonth = new Date(year, monthNum, 0).getDate();
                
                let html = '<div class="attendance-list">';
                
                for (let day = 1; day <= daysInMonth; day++) {
                    const dateStr = `${year}-${monthNum.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                    const isSchoolDay = schoolDaysMap[dateStr];
                    const isAttended = attendanceDays.includes(dateStr);
                    
                    let className = 'attendance-day';
                    let status = '';
                    
                    if (isSchoolDay === 0) {
                        className += ' off-day';
                        status = 'إجازة';
                    } else if (isAttended) {
                        className += ' attended';
                        status = 'حاضر';
                    } else {
                        className += ' missed';
                        status = 'غائب';
                    }
                    
                    html += `
                        <div class="${className}">
                            <div>${day}</div>
                            <div style="font-size: 0.8rem;">${status}</div>
                        </div>
                    `;
                }
                
                html += '</div>';
                container.innerHTML = html;
            }
            
            updatePaymentButton(isPaid, paymentData) {
                const paymentBtn = document.getElementById('paymentBtn');
                
                if (isPaid) {
                    paymentBtn.textContent = 'تم الدفع';
                    paymentBtn.className = 'btn btn-primary paid';
                    paymentBtn.disabled = true;
                    paymentBtn.onclick = null;
                    
                    // Add payment info if available
                    if (paymentData && paymentData.paid_date) {
                        const paidDate = new Date(paymentData.paid_date).toLocaleDateString();
                        paymentBtn.title = `تم الدفع في ${paidDate}`;
                    }
                } else {
                    paymentBtn.innerHTML = '<i class="fas fa-check"></i> دفع الراتب';
                    paymentBtn.className = 'btn btn-primary';
                    paymentBtn.disabled = false;
                    paymentBtn.onclick = () => this.markSalaryPaid();
                    paymentBtn.title = '';
                }
            }
            
            async markSalaryPaid() {
                if (!this.currentTeacherId || !this.currentSalaryMonth) {
                    this.showAlert('لم يتم اختيار معلم أو شهر', 'error');
                    return;
                }
                
                try {
                    const response = await fetch('../../../api/mark-salary-paid.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            teacher_id: this.currentTeacherId,
                            month: this.currentSalaryMonth
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showAlert('تم وضع علامة مدفوع على الراتب بنجاح!', 'success');
                        // Reload salary data to update the UI
                        this.loadSalaryData(this.currentTeacherId, this.currentSalaryMonth);
                    } else {
                        this.showAlert('خطأ في وضع علامة مدفوع على الراتب: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error('خطأ:', error);
                    this.showAlert('خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
                }
            }
            
            viewSalaryHistory() {
                if (!this.currentTeacherId) {
                    this.showAlert('لم يتم اختيار معلم', 'error');
                    return;
                }
                
                this.loadSalaryHistory(this.currentTeacherId);
                this.openSalaryHistoryModal();
            }
            
            openSalaryHistoryModal() {
                document.getElementById('salaryHistoryModal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
            
            closeSalaryHistoryModal() {
                document.getElementById('salaryHistoryModal').style.display = 'none';
                document.body.style.overflow = 'auto';
            }
            
            async loadSalaryHistory(teacherId) {
                try {
                    const response = await fetch(`../../../api/get-salary-history.php?teacher_id=${teacherId}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        this.renderSalaryHistory(data.history);
                    } else {
                        this.showAlert('خطأ في تحميل سجل الرواتب: ' + data.message, 'error');
                    }
                } catch (error) {
                    console.error('خطأ:', error);
                    this.showAlert('خطأ في الشبكة أثناء تحميل سجل الرواتب.', 'error');
                }
            }
            
            renderSalaryHistory(history) {
                const container = document.getElementById('historyList');
                
                if (history.length === 0) {
                    container.innerHTML = '<p>لا يوجد سجل للرواتب.</p>';
                    return;
                }
                
                let html = '<div class="history-list">';
                
                history.forEach(item => {
                    const { month_name, salary_calculation, is_paid } = item;
                    const statusClass = is_paid ? 'paid' : 'unpaid';
                    const statusText = is_paid ? 'مدفوع' : 'غير مدفوع';
                    
                    html += `
                        <div class="history-item ${statusClass}" onclick="teachersManager.viewSalary(${this.currentTeacherId}, '${item.month}')">
                            <div class="history-header">
                                <div class="history-month">${month_name}</div>
                                <div class="history-status ${statusClass}">${statusText}</div>
                            </div>
                            <div class="history-details">
                                <div class="history-detail">
                                    <label>الراتب الشهري:</label>
                                    <span>$${salary_calculation.monthly_salary.toFixed(2)}</span>
                                </div>
                                <div class="history-detail">
                                    <label>أيام الحضور:</label>
                                    <span>${salary_calculation.attended_days_count}</span>
                                </div>
                                <div class="history-detail">
                                    <label>أيام الغياب:</label>
                                    <span>${salary_calculation.missed_working_days_count}</span>
                                </div>
                                <div class="history-detail">
                                    <label>الراتب النهائي:</label>
                                    <span>$${salary_calculation.calculated_salary.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                container.innerHTML = html;
            }
        }
        
        // Initialize the teachers manager when the page loads
        let teachersManager;
        document.addEventListener('DOMContentLoaded', function() {
            teachersManager = new TeachersManager();
        });
    </script>
</body>
</html>
