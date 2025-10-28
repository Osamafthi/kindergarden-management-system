// Configuration - Update this with your actual API endpoint
const API_ENDPOINT = '../../../api/add-classroom.php'; // Change this to your PHP API URL

// Arabic to Western numeral conversion utility
function convertArabicToWestern(text) {
  if (!text) return text;
  
  const arabicToWestern = {
    '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
    '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9'
  };
  
  return text.replace(/./g, function(char) {
    return arabicToWestern[char] || char;
  });
}

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
  // Get form elements
  const form = document.getElementById('classroomForm');
  const submitBtn = document.getElementById('submitBtn');
  const loading = document.getElementById('loading');
  const alert = document.getElementById('alert');
  
  // Get the capacity input field
  const capacityInput = document.getElementById('capacity');
  
  // Attach Arabic numeral conversion to capacity field
  if (capacityInput) {
    capacityInput.addEventListener('input', function() {
      const convertedValue = convertArabicToWestern(this.value);
      if (this.value !== convertedValue) {
        const cursorPosition = this.selectionStart;
        this.value = convertedValue;
        this.setSelectionRange(cursorPosition, cursorPosition);
      }
    });
  }
  
  // Get the room_number input field
  const roomNumberInput = document.getElementById('room_number');
  
  // Attach Arabic numeral conversion to room_number field
  if (roomNumberInput) {
    roomNumberInput.addEventListener('input', function() {
      const convertedValue = convertArabicToWestern(this.value);
      if (this.value !== convertedValue) {
        const cursorPosition = this.selectionStart;
        this.value = convertedValue;
        this.setSelectionRange(cursorPosition, cursorPosition);
      }
    });
  }

  // Form submission handler
  form.addEventListener('submit', async function(e) {
      e.preventDefault();
      
      // Get form data
      const formData = new FormData(form);
      const classroomData = {
          name: formData.get('name').trim(),
          grade_level: formData.get('grade_level'),
          capacity: parseInt(formData.get('capacity')),
          room_number: formData.get('room_number').trim()
      };

      // Validate data
      if (!validateForm(classroomData)) {
          return;
      }

      // Show loading state
      setLoadingState(true);
      hideAlert();

      try {
          // Send data to API
          const response = await fetch(API_ENDPOINT, {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'Accept': 'application/json'
              },
              body: JSON.stringify(classroomData)
          });

          const result = await response.json();

          if (response.ok) {
              // Success
              
              showAlert('تمت إضافة الفصل الدراسي بنجاح!', 'success');
              form.reset();
          } else {
              // API error
              throw new Error(result.message || 'فشل في إضافة الفصل الدراسي');
          }

      } catch (error) {
          console.error('خطأ:', error);
          showAlert(error.message || 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.', 'error');
      } finally {
          setLoadingState(false);
      }
  });

  // Form validation
  function validateForm(data) {
      if (!data.name || data.name.length < 2) {
          showAlert('يجب أن يكون اسم الفصل الدراسي على الأقل حرفين.', 'error');
          return false;
      }

      if (!data.grade_level) {
          showAlert('يرجى اختيار المستوى الدراسي.', 'error');
          return false;
      }

      if (!data.room_number || data.room_number.length < 1) {
          showAlert('رقم الغرفة مطلوب.', 'error');
          return false;
      }

      if (!data.capacity || data.capacity < 1 || data.capacity > 50) {
          showAlert('يجب أن تكون السعة بين 1 و 50 طالباً.', 'error');
          return false;
      }

      return true;
  }

  // Loading state management
  function setLoadingState(isLoading) {
      submitBtn.disabled = isLoading;
      loading.style.display = isLoading ? 'inline-block' : 'none';
      submitBtn.textContent = isLoading ? 'جاري الإضافة...' : 'إضافة الفصل الدراسي';
      
      if (isLoading) {
          submitBtn.appendChild(loading);
      }
  }

  // Alert management
  function showAlert(message, type) {
      alert.textContent = message;
      alert.className = `alert alert-${type}`;
      alert.style.display = 'block';
      
      // Auto-hide success messages after 5 seconds
      if (type === 'success') {
          setTimeout(() => {
              hideAlert();
          }, 5000);
      }
  }

  function hideAlert() {
      alert.style.display = 'none';
  }

  // Real-time validation feedback
  const inputs = document.querySelectorAll('.form-control');
  inputs.forEach(input => {
      input.addEventListener('blur', function() {
          validateField(this);
      });

      input.addEventListener('input', function() {
          // Clear invalid state when user starts typing
          if (this.classList.contains('invalid')) {
              this.classList.remove('invalid');
          }
      });
  });

  function validateField(field) {
      const value = field.value.trim();
      let isValid = true;

      switch (field.name) {
          case 'name':
              isValid = value.length >= 2;
              break;
          case 'grade_level':
              isValid = value !== '';
              break;
          case 'room_number':
              isValid = value.length >= 1;
              break;
          case 'capacity':
              const num = parseInt(value);
              isValid = num >= 1 && num <= 50;
              break;
      }

      field.classList.toggle('invalid', !isValid);
  }

  // Handle form reset
  form.addEventListener('reset', function() {
      hideAlert();
      inputs.forEach(input => {
          input.classList.remove('invalid');
      });
  });
}); // End DOMContentLoaded