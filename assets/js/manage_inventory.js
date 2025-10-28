// Manage Inventory JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the page
    initializePage();
    
    // Load initial stats
    loadStats();
    
    // Setup form handling
    setupFormHandling();
});

function initializePage() {
    console.log('تم تحميل صفحة إدارة المخزون');
    
    // Hide messages initially
    hideMessages();
    
    // Setup form validation
    setupFormValidation();
}

function hideMessages() {
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');
    
    if (successMessage) successMessage.style.display = 'none';
    if (errorMessage) errorMessage.style.display = 'none';
}

function showMessage(type, message) {
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');
    
    // Hide both messages first
    hideMessages();
    
    if (type === 'success') {
        if (successMessage) {
            const span = successMessage.querySelector('span');
            if (span) span.textContent = message;
            successMessage.style.display = 'flex';
            successMessage.classList.add('show');
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                successMessage.classList.remove('show');
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 300);
            }, 5000);
        }
    } else {
        if (errorMessage) {
            const span = errorMessage.querySelector('span');
            if (span) span.textContent = message;
            errorMessage.style.display = 'flex';
            errorMessage.classList.add('show');
            
            // Auto-hide after 7 seconds
            setTimeout(() => {
                errorMessage.classList.remove('show');
                setTimeout(() => {
                    errorMessage.style.display = 'none';
                }, 300);
            }, 7000);
        }
    }
}

function setupFormHandling() {
    const form = document.getElementById('addProductForm');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }
}

function setupFormValidation() {
    const form = document.getElementById('addProductForm');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', validateField);
        input.addEventListener('input', clearFieldError);
    });
}

function validateField(event) {
    const field = event.target;
    const value = field.value.trim();
    
    // Remove existing error styling
    field.classList.remove('error');
    
    // Validate based on field type
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'هذا الحقل مطلوب');
        return false;
    }
    
    if (field.type === 'text' && value.length < 2) {
        showFieldError(field, 'يجب أن يكون على الأقل حرفين');
        return false;
    }
    
    if (field.type === 'number') {
        const numValue = parseInt(value);
        if (isNaN(numValue) || numValue < 1) {
            showFieldError(field, 'يجب أن يكون رقماً موجباً');
            return false;
        }
        if (numValue > 9999) {
            showFieldError(field, 'القيمة القصوى هي 9999');
            return false;
        }
    }
    
    if (field.tagName === 'TEXTAREA' && value.length < 10) {
        showFieldError(field, 'يجب أن يكون الوصف على الأقل 10 أحرف');
        return false;
    }
    
    return true;
}

function showFieldError(field, message) {
    field.classList.add('error');
    
    // Remove existing error message
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Add new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '0.8rem';
    errorDiv.style.marginTop = '5px';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(event) {
    const field = event.target;
    field.classList.remove('error');
    
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

async function handleFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Validate all fields
    const inputs = form.querySelectorAll('input, textarea');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField({ target: input })) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        showMessage('error', 'يرجى إصلاح أخطاء النموذج قبل الإرسال');
        return;
    }
    
    // Prepare data for API
    const data = {
        name: formData.get('productName').trim(),
        description: formData.get('productDescription').trim(),
        quantity: parseInt(formData.get('productQuantity'))
    };
    
    // Show loading state
    setLoadingState(true);
    
    try {
        const response = await fetch('../../../api/add-product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            // Success
            showMessage('success', result.message);
            form.reset();
            
            // Update stats
            loadStats();
            
            // Scroll to top to show success message
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
        } else {
            // API error
            throw new Error(result.message || 'فشل في إضافة المنتج');
        }
        
    } catch (error) {
        console.error('خطأ:', error);
        showMessage('error', error.message || 'خطأ في الشبكة. يرجى المحاولة مرة أخرى.');
    } finally {
        setLoadingState(false);
    }
}

function setLoadingState(isLoading) {
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const loadingSpinner = document.getElementById('loadingSpinner');
    
    if (submitBtn) {
        submitBtn.disabled = isLoading;
    }
    
    if (isLoading) {
        if (submitText) submitText.style.display = 'none';
        if (loadingSpinner) loadingSpinner.style.display = 'inline-block';
    } else {
        if (submitText) submitText.style.display = 'inline-flex';
        if (loadingSpinner) loadingSpinner.style.display = 'none';
    }
}

function resetForm() {
    const form = document.getElementById('addProductForm');
    if (form) {
        form.reset();
        
        // Clear any error styling
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.classList.remove('error');
            const errorDiv = input.parentNode.querySelector('.field-error');
            if (errorDiv) {
                errorDiv.remove();
            }
        });
        
        // Hide messages
        hideMessages();
        
        // Focus on first field
        const firstInput = form.querySelector('input');
        if (firstInput) {
            firstInput.focus();
        }
    }
}

async function loadStats() {
    try {
        const response = await fetch('../../../api/get-products.php', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const result = await response.json();
        
        if (result.success) {
            const totalProducts = result.count;
            const totalItems = result.products.reduce((sum, product) => sum + parseInt(product.current_quantity), 0);
            
            // Update stats display
            const totalProductsEl = document.getElementById('totalProducts');
            const totalItemsEl = document.getElementById('totalItems');
            
            if (totalProductsEl) {
                totalProductsEl.textContent = totalProducts;
            }
            
            if (totalItemsEl) {
                totalItemsEl.textContent = totalItems;
            }
        }
        
    } catch (error) {
        console.error('خطأ في تحميل الإحصائيات:', error);
        // Don't show error to user for stats loading
    }
}

// Utility function to format numbers
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

// Add some visual feedback for form interactions
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addProductForm');
    if (form) {
        const inputs = form.querySelectorAll('input, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentNode.classList.remove('focused');
            });
        });
    }
});
