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
    <title>Control Inventory - Kindergarten System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/control_inventory.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1><i class="fas fa-chart-bar"></i> Control Inventory</h1>
                <p>Monitor and control product quantities</p>
            </div>
            <div class="header-actions">
                <a href="../index.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="manage_inventory.php" class="manage-button">
                    <i class="fas fa-plus"></i> Add Product
                </a>
            </div>
        </div>

        <!-- Loading State -->
        <div class="loading-container" id="loadingContainer">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <p>Loading products...</p>
        </div>

        <!-- Empty State -->
        <div class="empty-state" id="emptyState" style="display: none;">
            <div class="empty-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <h3>No Products Found</h3>
            <p>There are no products in the inventory yet.</p>
            <a href="manage_inventory.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add First Product
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
                    <h2><i class="fas fa-edit"></i> Edit Product Quantity</h2>
                    <span class="close" onclick="closeEditModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="editProductForm">
                        <input type="hidden" id="editProductId" name="productId">
                        
                        <div class="form-group">
                            <label for="editProductName">Product Name</label>
                            <input type="text" id="editProductName" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="editCurrentQuantity">Current Quantity</label>
                            <input type="number" id="editCurrentQuantity" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="editNewQuantity">New Quantity *</label>
                            <input type="number" id="editNewQuantity" name="newQuantity" class="form-control" min="1" required>
                            <small class="form-help">This will reset the progress bar to full (100%)</small>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="submitEditBtn">
                                <span id="editSubmitText">
                                    <i class="fas fa-save"></i> Update Quantity
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
                <span id="successText">Operation completed successfully!</span>
            </div>
            <div class="error-message" id="errorMessage">
                <i class="fas fa-exclamation-circle"></i>
                <span id="errorText">An error occurred. Please try again.</span>
            </div>
        </div>
    </div>

    <script src="../../../assets/js/control_inventory.js"></script>
    <script src="../../../assets/js/arabic-converter.js"></script>
</body>
</html>
