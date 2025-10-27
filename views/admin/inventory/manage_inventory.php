
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory - Kindergarten System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/manage_inventory.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <h1><i class="fas fa-boxes"></i> Manage Inventory</h1>
                <p>Add new products to the inventory system</p>
            </div>
            <div class="header-actions">
                <a href="../index.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="control_inventory_quantity.php" class="control-button">
                    <i class="fas fa-chart-bar"></i> Control Inventory
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <div class="message-container">
            <div class="success-message" id="successMessage">
                <i class="fas fa-check-circle"></i>
                <span>Product added successfully!</span>
            </div>
            <div class="error-message" id="errorMessage">
                <i class="fas fa-exclamation-circle"></i>
                <span>Please check the form and try again.</span>
            </div>
        </div>

        <!-- Form Container -->
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Product</h2>
                <p>Fill in the details below to add a new product to the inventory</p>
            </div>

            <form id="addProductForm">
                <div class="form-group">
                    <label for="productName">
                        <i class="fas fa-tag"></i> Product Name *
                    </label>
                    <input 
                        type="text" 
                        id="productName" 
                        name="productName" 
                        class="form-control" 
                        placeholder="Enter product name (e.g., Pencils, Notebooks, Art Supplies)"
                        required
                        maxlength="255"
                    >
                    <small class="form-help">Choose a clear, descriptive name for the product</small>
                </div>

                <div class="form-group">
                    <label for="productDescription">
                        <i class="fas fa-align-left"></i> Product Description *
                    </label>
                    <textarea 
                        id="productDescription" 
                        name="productDescription" 
                        class="form-control" 
                        rows="4" 
                        placeholder="Enter detailed description of the product..."
                        required
                    ></textarea>
                    <small class="form-help">Provide a detailed description of the product</small>
                </div>

                <div class="form-group">
                    <label for="productQuantity">
                        <i class="fas fa-hashtag"></i> Initial Quantity *
                    </label>
                    <input 
                        type="number" 
                        id="productQuantity" 
                        name="productQuantity" 
                        class="form-control" 
                        min="1" 
                        max="9999"
                        placeholder="Enter initial quantity"
                        required
                    >
                    <small class="form-help">Enter the initial quantity of this product in stock</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span id="submitText">
                            <i class="fas fa-plus"></i> Add Product
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
                    <p>Total Products</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <h3 id="totalItems">0</h3>
                    <p>Total Items</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../../../assets/js/manage_inventory.js"></script>
    <script src="../../../assets/js/arabic-converter.js"></script>
</body>
</html>
