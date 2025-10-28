
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
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المخزون - نظام الروضة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/manage_inventory.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1><i class="fas fa-boxes"></i> إدارة المخزون</h1>
                <p>إضافة منتجات جديدة إلى نظام المخزون</p>
            </div>
            <div class="header-actions">
                <a href="../index.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> العودة إلى الصفحة الرئيسية
                </a>
                <a href="control_inventory_quantity.php" class="control-button">
                    <i class="fas fa-chart-bar"></i> التحكم في المخزون
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div class="message-container">
            <div class="success-message" id="successMessage">
                <i class="fas fa-check-circle"></i>
                <span>تمت إضافة المنتج بنجاح!</span>
            </div>
            <div class="error-message" id="errorMessage">
                <i class="fas fa-exclamation-circle"></i>
                <span>يرجى التحقق من النموذج والمحاولة مرة أخرى.</span>
            </div>
        </div>

        <!-- Form Container -->
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-plus-circle"></i> إضافة منتج جديد</h2>
                <p>املأ التفاصيل أدناه لإضافة منتج جديد إلى المخزون</p>
            </div>

            <form id="addProductForm">
                <div class="form-group">
                    <label for="productName">
                        <i class="fas fa-tag"></i> اسم المنتج *
                    </label>
                    <input 
                        type="text" 
                        id="productName" 
                        name="productName" 
                        class="form-control" 
                        placeholder="أدخل اسم المنتج (مثال: أقلام، دفاتر، أدوات فنية)"
                        required
                        maxlength="255"
                    >
                    <small class="form-help">اختر اسماً واضحاً ووصفياً للمنتج</small>
                </div>

                <div class="form-group">
                    <label for="productDescription">
                        <i class="fas fa-align-left"></i> وصف المنتج *
                    </label>
                    <textarea 
                        id="productDescription" 
                        name="productDescription" 
                        class="form-control" 
                        rows="4" 
                        placeholder="أدخل وصفاً تفصيلياً للمنتج..."
                        required
                    ></textarea>
                    <small class="form-help">قدم وصفاً تفصيلياً للمنتج</small>
                </div>

                <div class="form-group">
                    <label for="productQuantity">
                        <i class="fas fa-hashtag"></i> الكمية الأولية *
                    </label>
                    <input 
                        type="number" 
                        id="productQuantity" 
                        name="productQuantity" 
                        class="form-control" 
                        min="1" 
                        max="9999"
                        placeholder="أدخل الكمية الأولية"
                        required
                    >
                    <small class="form-help">أدخل الكمية الأولية لهذا المنتج في المخزون</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-undo"></i> إعادة تعيين النموذج
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span id="submitText">
                            <i class="fas fa-plus"></i> إضافة المنتج
                        </span>
                        <div class="loading" id="loadingSpinner" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </button>
                </div>
            </form>
        </div>

        <!-- Quick Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-content">
                    <h3 id="totalProducts">0</h3>
                    <p>إجمالي المنتجات</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3 id="totalItems">0</h3>
                    <p>إجمالي العناصر</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../../../assets/js/manage_inventory.js?v=<?php echo time(); ?>"></script>
    <script src="../../../assets/js/arabic-converter.js"></script>
</body>
</html>
