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
    <title>إضافة فصل جديد - نظام الروضة</title>
    <link rel="stylesheet" href="../../../assets/css/add_classroom.css"> 
</head>
<body>
<div id="button">
        <a href="../index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> العودة إلى  الصفحة الرئيسية   
            </a>
    </div>
    <div class="container">
        <div class="header">
            <h1>🏫 إضافة فصل  جديد</h1>
            <p>أدخل تفاصيل الفصل  أدناه</p>
        </div>
       
        <div id="alert" class="alert"></div>
     
        <form id="classroomForm">
            <div class="form-group">
                <label for="name">اسم الفصل  *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-control" 
                    placeholder="مثال: فصل الشمس، فصل عمرو ابن الخطاب"
                    required
                    maxlength="100"
                >
            </div>


            <div class="form-row">
                <div class="form-group">
                    <label for="grade_level">المستوى الدراسي *</label>
                    <select id="grade_level" name="grade_level" class="form-control" required>
                        <option value="">اختر المستوى الدراسي</option>
                        <option value="Pre-K">ما قبل الروضة</option>
                        <option value="Kindergarten">روضة</option>
                        <option value="K-1">روضة-1</option>
                        <option value="K-2">روضة-2</option>
                        <option value="Mixed Age">أعمار مختلطة</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="room_number">رقم الغرفة *</label>
                    <input 
                        type="text" 
                        id="room_number" 
                        name="room_number" 
                        class="form-control" 
                        placeholder="مثال: 101، أ-15"
                        required
                        maxlength="10"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="capacity">السعة (عدد الطلاب) *</label>
                <input 
                    type="number" 
                    id="capacity" 
                    name="capacity" 
                    class="form-control" 
                    placeholder="الحد الأقصى لعدد الطلاب"
                    required
                    min="1"
                    max="50"
                >
            </div>

            <button type="submit" class="btn" id="submitBtn">
                إضافة الفصل الدراسي
                <span class="loading" id="loading"></span>
            </button>
        </form>
    </div>
    <script src="../../../assets/js/arabic-converter.js"></script>
    <script src="../../../assets/js/add_classroom.js?v=<?php echo time(); ?>"></script>

</body>
</html>