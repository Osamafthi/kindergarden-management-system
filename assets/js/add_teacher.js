document.addEventListener('DOMContentLoaded', function() {
    const teacherForm = document.getElementById('teacherForm');
    const alertSuccess = document.getElementById('alertSuccess');
    const alertError = document.getElementById('alertError');
    const errorMessage = document.getElementById('errorMessage');
    const submitButton = document.getElementById('submitButton');
    const submitSpinner = document.getElementById('submitSpinner');
    
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
full_name: document.getElementById('fullName').value,        // Changed from fullName
phone_number: document.getElementById('phone').value,       // Changed from phone
email: document.getElementById('email').value,
gender: document.querySelector('input[name="gender"]:checked').value,
// Changed from position
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
                errorMessage.textContent = data.message || 'There was an error adding the teacher.';
                alertError.style.display = 'block';
                alertSuccess.style.display = 'none';
            }
        })
        .catch(error => {
            // Show error message
            console.error('Fetch error:', error);
            errorMessage.textContent = 'Network error. Please try again.';
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
        // Basic validation - you can expand this
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;
        
        // Simple email validation
        if (!/\S+@\S+\.\S+/.test(email)) {
            errorMessage.textContent = 'Please enter a valid email address.';
            alertError.style.display = 'block';
            return false;
        }
        
        // Simple phone validation (at least 10 digits)
        if (phone.replace(/\D/g, '').length < 10) {
            errorMessage.textContent = 'Please enter a valid phone number.';
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