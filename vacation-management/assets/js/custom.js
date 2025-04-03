// Common utility functions

/**
 * Show alert message
 * @param {string} message - The message to display
 * @param {string} type - Bootstrap alert type (success, danger, warning, info)
 * @param {string} containerId - ID of the container element
 */
function showAlert(message, type = 'danger', containerId = 'alertMessage') {
    const alertContainer = document.getElementById(containerId);
    if (alertContainer) {
        alertContainer.className = `alert alert-${type}`;
        alertContainer.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}`;
        alertContainer.style.display = 'block';
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            alertContainer.style.display = 'none';
        }, 5000);
    }
}

/**
 * Validate email format
 * @param {string} email
 * @returns {boolean}
 */
function isValidEmail(email) {
    const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return re.test(email);
}

/**
 * Validate password strength
 * @param {string} password
 * @returns {boolean}
 */
function isValidPassword(password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
    return re.test(password);
}

/**
 * Format date to YYYY-MM-DD
 * @param {Date} date
 * @returns {string}
 */
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

/**
 * Calculate number of days between two dates
 * @param {Date} startDate
 * @param {Date} endDate
 * @returns {number}
 */
function calculateDays(startDate, endDate) {
    const diffTime = Math.abs(endDate - startDate);
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
}

/**
 * Handle form submission with Ajax
 * @param {string} formId - ID of the form element
 * @param {string} url - URL to submit the form to
 * @param {Function} successCallback - Callback function on success
 * @param {Function} errorCallback - Callback function on error
 */
function handleFormSubmit(formId, url, successCallback, errorCallback) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Create FormData object
        const formData = new FormData(form);
        
        // Disable submit button and show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
        submitBtn.disabled = true;

        // Send Ajax request
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                successCallback(data);
            } else {
                throw new Error(data.message || 'An error occurred');
            }
        })
        .catch(error => {
            errorCallback(error.message);
            // Reset button state
            submitBtn.innerHTML = originalBtnText;
            submitBtn.disabled = false;
        });
    });
}

/**
 * Initialize date range picker
 * @param {string} startDateId - ID of start date input
 * @param {string} endDateId - ID of end date input
 * @param {Function} callback - Callback function when dates change
 */
function initializeDateRangePicker(startDateId, endDateId, callback) {
    const startDate = document.getElementById(startDateId);
    const endDate = document.getElementById(endDateId);
    
    if (!startDate || !endDate) return;

    // Set min date to today
    const today = new Date();
    startDate.min = formatDate(today);
    endDate.min = formatDate(today);

    // Update end date min when start date changes
    startDate.addEventListener('change', function() {
        endDate.min = this.value;
        if (endDate.value && endDate.value < this.value) {
            endDate.value = this.value;
        }
        if (callback && endDate.value) {
            callback(new Date(this.value), new Date(endDate.value));
        }
    });

    // Call callback when end date changes
    endDate.addEventListener('change', function() {
        if (callback && startDate.value) {
            callback(new Date(startDate.value), new Date(this.value));
        }
    });
}

/**
 * Format currency
 * @param {number} amount
 * @param {string} currency
 * @returns {string}
 */
function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

/**
 * Confirm action with modal
 * @param {string} message
 * @param {Function} callback
 */
function confirmAction(message, callback) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmBtn">Confirm</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
    
    document.getElementById('confirmBtn').onclick = function() {
        modalInstance.hide();
        callback();
        modal.remove();
    };
    
    modal.addEventListener('hidden.bs.modal', function() {
        modal.remove();
    });
}