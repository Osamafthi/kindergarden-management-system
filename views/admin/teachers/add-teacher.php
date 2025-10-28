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
    <title>إضافة معلم - نظام إدارة الروضة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/add-teacher.css?v=<?php echo time(); ?>"> 
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>إضافة معلم جديد</h1>
            <a href="../index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> العودة إلى الصفحة الرئيسية
            </a>
        </div>
        
        <!-- Alert Messages -->
        <div id="alertSuccess" class="alert alert-success ">
            <i class="fas fa-check-circle"></i> تمت إضافة المعلم بنجاح!
        </div>
        
        <div id="alertError" class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <span id="errorMessage">حدث خطأ أثناء إضافة المعلم.</span>
        </div>
        
        <!-- Teacher Form -->
        <div class="form-card">
            <div class="form-header">
                <h2>معلومات المعلم</h2>
            </div>
            
            <div class="form-body">
                <form id="teacherForm">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="fullName">الاسم الكامل *</label>
                                <input type="text" id="fullName" name="fullName" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="phone">رقم الهاتف *</label>
                                <input type="tel" id="phone" name="phone" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="email">البريد الإلكتروني *</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="password">كلمة المرور *</label>
                                <div style="position: relative;">
                                    <input type="password" id="password" name="password" class="form-control" minlength="6" required>
                                    <i class="fas fa-eye password-toggle" id="togglePassword" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
                                </div>
                                <small style="color: #666; font-size: 0.85em;">الحد الأدنى 6 أحرف</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="confirmPassword">تأكيد كلمة المرور *</label>
                                <div style="position: relative;">
                                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" minlength="6" required>
                                    <i class="fas fa-eye password-toggle" id="toggleConfirmPassword" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;"></i>
                                </div>
                                <small style="color: #666; font-size: 0.85em;">أعد إدخال كلمة المرور</small>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <!-- Empty column for layout balance -->
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label>الجنس *</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="genderMale" name="gender" value="male" required>
                                        <label for="genderMale">ذكر</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="genderFemale" name="gender" value="female">
                                        <label for="genderFemale">أنثى</label>
                                    </div>
                                   
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <!-- Empty column for layout balance -->
                        </div>
                    </div>
                    
                    <div class="form-row">
                       
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="hourlyRate">الأجر بالساعة ($) *</label>
                                <input type="number" id="hourlyRate" name="hourlyRate" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="monthlySalary">الراتب الشهري ($) *</label>
                                <input type="number" id="monthlySalary" name="monthlySalary" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <!-- Empty column for layout balance -->
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">مسح النموذج</button>
                        <button type="submit" id="submitButton" class="btn btn-primary">
                            <div class="spinner" id="submitSpinner"></div>
                            <span>إضافة المعلم</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- External JavaScript -->
    <script src="../../../assets/js/arabic-converter.js"></script>
    <script src="../../../assets/js/add_teacher.js?v=<?php echo time(); ?>"></script>
</body>
</html>