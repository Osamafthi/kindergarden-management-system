// Control Inventory JavaScript
let products = [];

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the page
    initializePage();
    
    // Load products
    loadProducts();
    
    // Setup modal handling
    setupModalHandling();
});

function initializePage() {
    console.log('Control Inventory page loaded');
    
    // Hide messages initially
    hideMessages();
}

function hideMessages() {
    const messageContainer = document.getElementById('messageContainer');
    if (messageContainer) {
        messageContainer.style.display = 'none';
    }
}

function showMessage(type, message) {
    const messageContainer = document.getElementById('messageContainer');
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');
    const successText = document.getElementById('successText');
    const errorText = document.getElementById('errorText');
    
    // Hide both messages first
    if (successMessage) successMessage.style.display = 'none';
    if (errorMessage) errorMessage.style.display = 'none';
    
    if (messageContainer) {
        messageContainer.style.display = 'block';
    }
    
    if (type === 'success') {
        if (successMessage && successText) {
            successText.textContent = message;
            successMessage.style.display = 'flex';
            successMessage.classList.add('show');
            
            // Auto-hide after 4 seconds
            setTimeout(() => {
                successMessage.classList.remove('show');
                setTimeout(() => {
                    if (messageContainer) {
                        messageContainer.style.display = 'none';
                    }
                }, 300);
            }, 4000);
        }
    } else {
        if (errorMessage && errorText) {
            errorText.textContent = message;
            errorMessage.style.display = 'flex';
            errorMessage.classList.add('show');
            
            // Auto-hide after 6 seconds
            setTimeout(() => {
                errorMessage.classList.remove('show');
                setTimeout(() => {
                    if (messageContainer) {
                        messageContainer.style.display = 'none';
                    }
                }, 300);
            }, 6000);
        }
    }
}

async function loadProducts() {
    const loadingContainer = document.getElementById('loadingContainer');
    const emptyState = document.getElementById('emptyState');
    const productsGrid = document.getElementById('productsGrid');
    
    try {
        const response = await fetch('../../../api/get-products.php', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const result = await response.json();
        
        if (result.success) {
            products = result.products;
            
            if (products.length === 0) {
                // Show empty state
                if (loadingContainer) loadingContainer.style.display = 'none';
                if (emptyState) emptyState.style.display = 'block';
                if (productsGrid) productsGrid.style.display = 'none';
            } else {
                // Show products
                if (loadingContainer) loadingContainer.style.display = 'none';
                if (emptyState) emptyState.style.display = 'none';
                if (productsGrid) {
                    productsGrid.style.display = 'grid';
                    renderProducts(products);
                }
            }
        } else {
            throw new Error(result.message || 'Failed to load products');
        }
        
    } catch (error) {
        console.error('Error loading products:', error);
        showMessage('error', 'Failed to load products. Please refresh the page.');
        
        if (loadingContainer) loadingContainer.style.display = 'none';
        if (emptyState) emptyState.style.display = 'block';
        if (productsGrid) productsGrid.style.display = 'none';
    }
}

function renderProducts(products) {
    const productsGrid = document.getElementById('productsGrid');
    if (!productsGrid) return;
    
    productsGrid.innerHTML = '';
    
    products.forEach(product => {
        const productCard = createProductCard(product);
        productsGrid.appendChild(productCard);
    });
}

function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.dataset.productId = product.id;
    
    // Calculate progress percentage
    const percentage = Math.round((product.current_quantity / product.original_quantity) * 100);
    const progressColor = getProgressColor(percentage);
    
    card.innerHTML = `
        <div class="product-header">
            <div class="product-name">
                <i class="fas fa-box"></i>
                ${escapeHtml(product.name)}
            </div>
            <div class="product-quantity">
                ${product.current_quantity} / ${product.original_quantity} items
            </div>
        </div>
        <div class="product-body">
            <div class="product-description">
                ${escapeHtml(product.description)}
            </div>
            <div class="progress-container">
                <div class="progress-label">
                    <span>Stock Level</span>
                    <span class="progress-percentage">${percentage}%</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar ${progressColor}" style="width: ${percentage}%"></div>
                </div>
            </div>
            <div class="product-actions">
                <button class="action-btn decrease-btn" onclick="decreaseQuantity(${product.id})" ${product.current_quantity <= 0 ? 'disabled' : ''}>
                    <i class="fas fa-minus"></i> -1
                </button>
                <button class="action-btn increase-btn" onclick="increaseQuantity(${product.id})">
                    <i class="fas fa-plus"></i> +1
                </button>
                <button class="action-btn edit-btn" onclick="openEditModal(${product.id})">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
        </div>
    `;
    
    return card;
}

function getProgressColor(percentage) {
    if (percentage >= 67) {
        return 'green';
    } else if (percentage >= 34) {
        return 'yellow';
    } else {
        return 'red';
    }
}

async function decreaseQuantity(productId) {
    await updateQuantity(productId, 1, 'OUT');
}

async function increaseQuantity(productId) {
    await updateQuantity(productId, 1, 'IN');
}

async function updateQuantity(productId, quantity, movementType) {
    try {
        const response = await fetch('../../../api/update-quantity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity,
                movement_type: movementType
            })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            // Update the product in our local array
            const productIndex = products.findIndex(p => p.id == productId);
            if (productIndex !== -1) {
                products[productIndex].current_quantity = result.new_quantity;
                
                // Re-render the specific product card
                updateProductCard(productId, products[productIndex]);
            }
            
            showMessage('success', `${movementType === 'IN' ? 'Increased' : 'Decreased'} quantity successfully`);
            
        } else {
            throw new Error(result.message || 'Failed to update quantity');
        }
        
    } catch (error) {
        console.error('Error updating quantity:', error);
        showMessage('error', error.message || 'Failed to update quantity. Please try again.');
    }
}

function updateProductCard(productId, product) {
    const card = document.querySelector(`[data-product-id="${productId}"]`);
    if (!card) return;
    
    // Calculate new progress
    const percentage = Math.round((product.current_quantity / product.original_quantity) * 100);
    const progressColor = getProgressColor(percentage);
    
    // Update quantity display
    const quantityEl = card.querySelector('.product-quantity');
    if (quantityEl) {
        quantityEl.textContent = `${product.current_quantity} / ${product.original_quantity} items`;
    }
    
    // Update progress bar
    const progressPercentage = card.querySelector('.progress-percentage');
    const progressBar = card.querySelector('.progress-bar');
    
    if (progressPercentage) {
        progressPercentage.textContent = `${percentage}%`;
    }
    
    if (progressBar) {
        progressBar.className = `progress-bar ${progressColor}`;
        progressBar.style.width = `${percentage}%`;
    }
    
    // Update decrease button state
    const decreaseBtn = card.querySelector('.decrease-btn');
    if (decreaseBtn) {
        decreaseBtn.disabled = product.current_quantity <= 0;
    }
}

function openEditModal(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;
    
    const modal = document.getElementById('editProductModal');
    const productNameInput = document.getElementById('editProductName');
    const currentQuantityInput = document.getElementById('editCurrentQuantity');
    const newQuantityInput = document.getElementById('editNewQuantity');
    const productIdInput = document.getElementById('editProductId');
    
    if (modal && productNameInput && currentQuantityInput && newQuantityInput && productIdInput) {
        productNameInput.value = product.name;
        currentQuantityInput.value = product.current_quantity;
        newQuantityInput.value = product.current_quantity;
        productIdInput.value = productId;
        
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Focus on new quantity input
        setTimeout(() => {
            newQuantityInput.focus();
            newQuantityInput.select();
        }, 100);
    }
}

function closeEditModal() {
    const modal = document.getElementById('editProductModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset form
        const form = document.getElementById('editProductForm');
        if (form) {
            form.reset();
        }
    }
}

function setupModalHandling() {
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('editProductModal');
        if (event.target === modal) {
            closeEditModal();
        }
    };
    
    // Handle edit form submission
    const editForm = document.getElementById('editProductForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditSubmit);
    }
}

async function handleEditSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const productId = parseInt(formData.get('productId'));
    const newQuantity = parseInt(formData.get('newQuantity'));
    
    // Validate quantity
    if (isNaN(newQuantity) || newQuantity < 1) {
        showMessage('error', 'Please enter a valid quantity (minimum 1)');
        return;
    }
    
    // Show loading state
    setEditLoadingState(true);
    
    try {
        const response = await fetch('../../../api/update-quantity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                product_id: productId,
                quantity: newQuantity,
                movement_type: 'IN',
                reset_original: true
            })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            // Update the product in our local array
            const productIndex = products.findIndex(p => p.id == productId);
            if (productIndex !== -1) {
                products[productIndex].current_quantity = result.new_quantity;
                products[productIndex].original_quantity = result.new_quantity;
                
                // Re-render the specific product card
                updateProductCard(productId, products[productIndex]);
            }
            
            showMessage('success', 'Product quantity updated successfully');
            closeEditModal();
            
        } else {
            throw new Error(result.message || 'Failed to update product quantity');
        }
        
    } catch (error) {
        console.error('Error updating product:', error);
        showMessage('error', error.message || 'Failed to update product. Please try again.');
    } finally {
        setEditLoadingState(false);
    }
}

function setEditLoadingState(isLoading) {
    const submitBtn = document.getElementById('submitEditBtn');
    const submitText = document.getElementById('editSubmitText');
    const loadingSpinner = document.getElementById('editLoadingSpinner');
    
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

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add keyboard shortcuts
document.addEventListener('keydown', function(event) {
    // ESC key to close modal
    if (event.key === 'Escape') {
        closeEditModal();
    }
});

// Add some visual feedback for button interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add click animation to action buttons
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('action-btn')) {
            event.target.style.transform = 'scale(0.95)';
            setTimeout(() => {
                event.target.style.transform = '';
            }, 150);
        }
    });
});
