document.addEventListener('DOMContentLoaded', function() {
    const teacherForm = document.getElementById('teacherForm');
    const alertSuccess = document.getElementById('alertSuccess');
    const alertError = document.getElementById('alertError');
    const errorMessage = document.getElementById('errorMessage');
    const submitButton = document.getElementById('submitButton');
    const submitSpinner = document.getElementById('submitSpinner');
    
    // Password visibility toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const confirmPasswordField = document.getElementById('confirmPassword');
    
    // Toggle password visibility
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Toggle eye icon
            if (type === 'text') {
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    }
    
    // Toggle confirm password visibility
    if (toggleConfirmPassword) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordField.setAttribute('type', type);
            
            // Toggle eye icon
            if (type === 'text') {
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    }
    
    teacherForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Show loading state
        submitSpinner.style.display = 'inline-block';
        submitButton.disabled = true;
        
        const formData = {
full_name: document.getElementById('fullName').value,
phone_number: document.getElementById('phone').value,
email: document.getElementById('email').value,
password: document.getElementById('password').value,
gender: document.querySelector('input[name="gender"]:checked').value,
hourly_rate: document.getElementById('hourlyRate').value,
monthly_salary: document.getElementById('monthlySalary').value
};
        
        // Send data to PHP endpoint
        fetch('../../../api/add-teacher.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
             
                alertSuccess.style.display = 'block';
                alertError.style.display = 'none';
                console.log(data);
            
                // Reset form
                teacherForm.reset();
            } else {
                // Show error message
                errorMessage.textContent = data.message || 'حدث خطأ أثناء إضافة المعلم.';
                alertError.style.display = 'block';
                alertSuccess.style.display = 'none';
            }
        })
        .catch(error => {
            // Show error message
            console.error('خطأ في الطلب:', error);
            errorMessage.textContent = 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.';
            alertError.style.display = 'block';
            alertSuccess.style.display = 'none';
        })
        .finally(() => {
            // Hide loading state
            submitSpinner.style.display = 'none';
            submitButton.disabled = false;
        });
    });
    
    function validateForm() {
        // Hide any previous error messages
        alertError.style.display = 'none';
        
        // Basic validation - you can expand this
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        // Simple email validation
        if (!/\S+@\S+\.\S+/.test(email)) {
            errorMessage.textContent = 'يرجى إدخال عنوان بريد إلكتروني صالح.';
            alertError.style.display = 'block';
            return false;
        }
        
        // Password validation
        if (password.length < 6) {
            errorMessage.textContent = 'يجب أن تكون كلمة المرور 6 أحرف على الأقل.';
            alertError.style.display = 'block';
            return false;
        }
        
        // Confirm password validation
        if (confirmPassword.length < 6) {
            errorMessage.textContent = 'يجب أن يكون تأكيد كلمة المرور 6 أحرف على الأقل.';
            alertError.style.display = 'block';
            return false;
        }
        
        // Check if passwords match
        if (password !== confirmPassword) {
            errorMessage.textContent = 'كلمات المرور غير متطابقة. يرجى التأكد من أن كلتا كلمتي المرور متطابقتان.';
            alertError.style.display = 'block';
            return false;
        }
        
        // Simple phone validation (at least 10 digits)
        if (phone.replace(/\D/g, '').length < 10) {
            errorMessage.textContent = 'يرجى إدخال رقم هاتف صالح.';
            alertError.style.display = 'block';
            return false;
        }
        
        return true;
    }
    
    // Real-time validation for hourly rate and monthly salary
    document.getElementById('hourlyRate').addEventListener('input', function() {
        if (this.value < 0) {
            this.value = 0;
        }
    });
    
    document.getElementById('monthlySalary').addEventListener('input', function() {
        if (this.value < 0) {
            this.value = 0;
        }
    });
});   