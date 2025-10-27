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
    <title>Add Classroom - Kindergarten System</title>
    <link rel="stylesheet" href="../../../assets/css/add_classroom.css"> 
</head>
<body>
<div id="button">
        <a href="../index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
    </div>
    <div class="container">
        <div class="header">
            <h1>🏫 Add New Classroom</h1>
            <p>Enter the classroom details below</p>
        </div>
       
        <div id="alert" class="alert"></div>
     
        <form id="classroomForm">
            <div class="form-group">
                <label for="name">Classroom Name *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-control" 
                    placeholder="e.g., Sunshine Room, Rainbow Class"
                    required
                    maxlength="100"
                >
            </div>


            <div class="form-row">
                <div class="form-group">
                    <label for="grade_level">Grade Level *</label>
                    <select id="grade_level" name="grade_level" class="form-control" required>
                        <option value="">Select Grade Level</option>
                        <option value="Pre-K">Pre-K</option>
                        <option value="Kindergarten">Kindergarten</option>
                        <option value="K-1">K-1</option>
                        <option value="K-2">K-2</option>
                        <option value="Mixed Age">Mixed Age</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="room_number">Room Number *</label>
                    <input 
                        type="text" 
                        id="room_number" 
                        name="room_number" 
                        class="form-control" 
                        placeholder="e.g., 101, A-15"
                        required
                        maxlength="10"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="capacity">Capacity (Number of Students) *</label>
                <input 
                    type="number" 
                    id="capacity" 
                    name="capacity" 
                    class="form-control" 
                    placeholder="Maximum number of students"
                    required
                    min="1"
                    max="50"
                >
            </div>

            <button type="submit" class="btn" id="submitBtn">
                Add Classroom
                <span class="loading" id="loading"></span>
            </button>
        </form>
    </div>
    <script src="../../../assets/js/arabic-converter.js"></script>
    <script src="../../../assets/js/add_classroom.js"></script>

</body>
</html>