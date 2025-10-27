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
    <title>Add Teacher - Kindergarten Admin System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../../assets/css/add-teacher.css"> 
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Add New Teacher</h1>
            <a href="../index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <!-- Alert Messages -->
        <div id="alertSuccess" class="alert alert-success ">
            <i class="fas fa-check-circle"></i> Teacher added successfully!
        </div>
        
        <div id="alertError" class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <span id="errorMessage">There was an error adding the teacher.</span>
        </div>
        
        <!-- Teacher Form -->
        <div class="form-card">
            <div class="form-header">
                <h2>Teacher Information</h2>
            </div>
            
            <div class="form-body">
                <form id="teacherForm">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="fullName">Full Name *</label>
                                <input type="text" id="fullName" name="fullName" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label>Gender *</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="genderMale" name="gender" value="male" required>
                                        <label for="genderMale">Male</label>
                                    </div>
                                    <div class="radio-option">
                                        <input type="radio" id="genderFemale" name="gender" value="female">
                                        <label for="genderFemale">Female</label>
                                    </div>
                                   
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                       
                        
                        <div class="form-col">
                            <div class="form-group">
                                <label for="hourlyRate">Hourly Rate ($) *</label>
                                <input type="number" id="hourlyRate" name="hourlyRate" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="monthlySalary">Monthly Salary ($) *</label>
                                <input type="number" id="monthlySalary" name="monthlySalary" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="form-col">
                            <!-- Empty column for layout balance -->
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="reset" class="btn btn-secondary">Clear Form</button>
                        <button type="submit" id="submitButton" class="btn btn-primary">
                            <div class="spinner" id="submitSpinner"></div>
                            <span>Add Teacher</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- External JavaScript -->
    <script src="../../../assets/js/arabic-converter.js"></script>
    <script src="../../../assets/js/add_teacher.js"></script>
</body>
</html>