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
    <title>التحكم في المخزون - نظام الروضة</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/control_inventory.css">
    <link rel="stylesheet" href="../../../assets/css/compatibility.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1><i class="fas fa-chart-bar"></i> التحكم في المخزون</h1>
                <p>مراقبة والتحكم في كميات المنتجات</p>
            </div>
            <div class="header-actions">
                <a href="../index.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> العودة إلى الصفحة الرئيسية
                </a>
                <a href="manage_inventory.php" class="manage-button">
                    <i class="fas fa-plus"></i> إضافة منتج
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div class="loading-container" id="loadingContainer">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>جاري تحميل المنتجات...</p>
        </div>

        <!-- Empty State -->
        <div class="empty-state" id="emptyState" style="display: none;">
            <div class="empty-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <h3>لم يتم العثور على منتجات</h3>
            <p>لا توجد منتجات في المخزون حتى الآن.</p>
            <a href="manage_inventory.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة أول منتج
            </a>
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="productsGrid" style="display: none;">
            <!-- Products will be loaded here via JavaScript -->
        </div>

        <!-- Edit Product Modal -->
        <div id="editProductModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-edit"></i> تعديل كمية المنتج</h2>
                    <span class="close" onclick="closeEditModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="editProductForm">
                        <input type="hidden" id="editProductId" name="productId">
                        
                        <div class="form-group">
                            <label for="editProductName">اسم المنتج</label>
                            <input type="text" id="editProductName" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="editCurrentQuantity">الكمية الحالية</label>
                            <input type="number" id="editCurrentQuantity" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="editNewQuantity">الكمية الجديدة *</label>
                            <input type="number" id="editNewQuantity" name="newQuantity" class="form-control" min="1" required>
                            <small class="form-help">سيؤدي هذا إلى إعادة تعيين شريط التقدم إلى 100%</small>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">إلغاء</button>
                            <button type="submit" class="btn btn-primary" id="submitEditBtn">
                                <span id="editSubmitText">
                                    <i class="fas fa-save"></i> تحديث الكمية
                                </span>
                                <div class="loading" id="editLoadingSpinner" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div class="message-container" id="messageContainer" style="display: none;">
            <div class="success-message" id="successMessage">
                <i class="fas fa-check-circle"></i>
                <span id="successText">تمت العملية بنجاح!</span>
            </div>
            <div class="error-message" id="errorMessage">
                <i class="fas fa-exclamation-circle"></i>
                <span id="errorText">حدث خطأ. يرجى المحاولة مرة أخرى.</span>
            </div>
        </div>
    </div>

    <script src="../../../assets/js/control_inventory.js?v=<?php echo time(); ?>"></script>
    <script src="../../../assets/js/arabic-converter.js"></script>
</body>
</html>
