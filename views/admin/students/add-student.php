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
    <title>Add Student - Kindergarten System</title>
   
    <link rel="stylesheet" href="../../../assets/css/add_student.css"> 

</head>
<body>
<div id="button">
        <a href="../index.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
    </div>
    <div class="container">
        <div class="header">
            <h1>🎓 Add New Student</h1>
            <p>Welcome to our Kindergarten Management System</p>
        </div>

        <div class="form-container">
            <div class="success-message" id="successMessage">
                Student added successfully! 🎉
            </div>

            <div class="error-message" id="errorMessage">
                Please check the form and try again.
            </div>

            <form id="addStudentForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name *</label>
                        <input type="text" id="firstName" name="firstName" required>
                    </div>

                    <div class="form-group">
                        <label for="lastName">Last Name *</label>
                        <input type="text" id="lastName" name="lastName" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="dateOfBirth">Date of Birth *</label>
                        <input type="date" id="dateOfBirth" name="dateOfBirth" required>
                    </div>

                    <div class="form-group">
                        <label for="enrollmentDate">Enrollment Date *</label>
                        <input type="date" id="enrollmentDate" name="enrollmentDate" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Gender *</label>
                    <div class="gender-group">
                        <div class="radio-option">
                            <input type="radio" id="male" name="gender" value="male" required>
                            <label for="male">👦 Male</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" id="female" name="gender" value="female" required>
                            <label for="female">👧 Female</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="studentLevel">Student Level at Enrollment *</label>
                    <select id="studentLevel" name="studentLevel" required>
                        <option value="">Select Level</option>
                        <option value="pre-k">Pre-K (3-4 years)</option>
                        <option value="kindergarten">Kindergarten (5-6 years)</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="photo">Student Photo</label>
                    <div class="photo-upload" id="photoUploadContainer">
                        <input type="file" id="photo" name="photo" accept="image/*">
                        <div class="photo-icon" id="photoIcon">📷</div>
                        <p id="uploadText">Click to upload a photo</p>
                        <p style="font-size: 0.9em; color: #666; margin-top: 5px;">
                            Supported formats: JPG, PNG, GIF (Max 5MB)
                        </p>
                        <div class="upload-status" id="uploadStatus"></div>
                        <img class="photo-preview" id="photoPreview" alt="Preview">
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    Add Student
                </button>
            </form>
        </div>
    </div>

    <script>
        // Set default enrollment date to today
        document.getElementById('enrollmentDate').valueAsDate = new Date();

        // Photo preview functionality
        document.getElementById('photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('photoPreview');
            const photoUpload = document.getElementById('photoUploadContainer');
            const photoIcon = document.getElementById('photoIcon');
            const uploadText = document.getElementById('uploadText');
            const uploadStatus = document.getElementById('uploadStatus');
            
            // Reset status
            uploadStatus.className = 'upload-status';
            uploadStatus.textContent = '';
            
            if (file) {
                // Validate file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    showUploadStatus('Photo size must be less than 5MB', 'error');
                    resetPhotoUpload();
                    return;
                }
                
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    showUploadStatus('Please select a valid image file', 'error');
                    resetPhotoUpload();
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    photoUpload.classList.add('has-image');
                    photoIcon.textContent = '✅';
                    uploadText.textContent = 'Photo selected: ' + file.name;
                    showUploadStatus('Photo ready for upload!', 'success');
                    photoUpload.style.padding = '15px';
                };
                reader.readAsDataURL(file);
            } else {
                resetPhotoUpload();
            }
        });

        // Show upload status message
        function showUploadStatus(message, type) {
            const uploadStatus = document.getElementById('uploadStatus');
            uploadStatus.textContent = message;
            uploadStatus.className = 'upload-status status-' + type;
        }

        // Reset photo upload UI
        function resetPhotoUpload() {
            const preview = document.getElementById('photoPreview');
            const photoUpload = document.getElementById('photoUploadContainer');
            const photoIcon = document.getElementById('photoIcon');
            const uploadText = document.getElementById('uploadText');
            const uploadStatus = document.getElementById('uploadStatus');
            
            preview.style.display = 'none';
            photoUpload.classList.remove('has-image');
            photoIcon.textContent = '📷';
            uploadText.textContent = 'Click to upload a photo';
            uploadStatus.className = 'upload-status';
            uploadStatus.textContent = '';
            photoUpload.style.padding = '30px';
            document.getElementById('photo').value = '';
        }

        // Check if photo is uploaded before form submission
        function validatePhoto() {
            const fileInput = document.getElementById('photo');
            if (fileInput.files.length === 0) {
                showUploadStatus('No photo selected (optional)', 'info');
                return true; // Photo is optional, so return true
            }
            
            return true; // Photo validation passed
        }

        // Form submission
        document.getElementById('addStudentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Validate photo before submission
            if (!validatePhoto()) {
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding Student...';
            
            try {
                // Create FormData object to handle file upload
                const formData = new FormData();
                
                // Add form fields to FormData
                formData.append('firstName', document.getElementById('firstName').value.trim());
                formData.append('lastName', document.getElementById('lastName').value.trim());
                formData.append('dateOfBirth', document.getElementById('dateOfBirth').value);
                formData.append('gender', document.querySelector('input[name="gender"]:checked').value);
                formData.append('enrollmentDate', document.getElementById('enrollmentDate').value);
                formData.append('studentLevel', document.getElementById('studentLevel').value);
                
                // Add photo if selected
                const photoFile = document.getElementById('photo').files[0];
                if (photoFile) {
                    formData.append('photo', photoFile);
                }
                
                // Validate required fields
                if (!validateForm()) {
                    throw new Error('Please fill in all required fields');
                }
                
                // Send to PHP API
                const response = await fetch('../../../api/add-student.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok && result.success) {
                    showSuccess('Student added successfully! 🎉');
                    resetForm();
                } else {
                    throw new Error(result.message || 'Failed to add student');
                }
                
            } catch (error) {
                showError(error.message);
                console.error('Error:', error);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Add Student';
            }
        });

        // Validation function
        function validateForm() {
            const requiredFields = ['firstName', 'lastName', 'dateOfBirth', 'enrollmentDate', 'studentLevel'];
            const genderSelected = document.querySelector('input[name="gender"]:checked');
            
            for (let field of requiredFields) {
                if (!document.getElementById(field).value.trim()) {
                    return false;
                }
            }
            
            if (!genderSelected) {
                return false;
            }
            
            // Validate dates
            const dob = new Date(document.getElementById('dateOfBirth').value);
            const enrollment = new Date(document.getElementById('enrollmentDate').value);
            const today = new Date();
            
            if (dob >= today) {
                showError('Date of birth must be in the past');
                return false;
            }
            
            if (enrollment > today) {
                showError('Enrollment date cannot be in the future');
                return false;
            }
            
            // Check minimum age (2 years old)
            const minAge = new Date();
            minAge.setFullYear(today.getFullYear() - 2);
            if (dob > minAge) {
                showError('Student must be at least 2 years old');
                return false;
            }
            
            return true;
        }

        // Show success message
        function showSuccess(message) {
            const successMsg = document.getElementById('successMessage');
            const errorMsg = document.getElementById('errorMessage');
            
            errorMsg.style.display = 'none';
            successMsg.textContent = message;
            successMsg.style.display = 'block';
            
            // Scroll to top to show message
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Hide after 5 seconds
            setTimeout(() => {
                successMsg.style.display = 'none';
            }, 5000);
        }

        // Show error message
        function showError(message) {
            const errorMsg = document.getElementById('errorMessage');
            const successMsg = document.getElementById('successMessage');
            
            successMsg.style.display = 'none';
            errorMsg.textContent = message;
            errorMsg.style.display = 'block';
            
            // Scroll to top to show message
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Hide after 5 seconds
            setTimeout(() => {
                errorMsg.style.display = 'none';
            }, 5000);
        }

        // Reset form
        function resetForm() {
            document.getElementById('addStudentForm').reset();
            resetPhotoUpload();
            document.getElementById('enrollmentDate').valueAsDate = new Date();
        }

        // Add input animations
        document.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
    <script src="../../../assets/js/arabic-converter.js"></script>
</body>
</html>