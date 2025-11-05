// Simple Ship Ticket Sales Form JavaScript
document.addEventListener('DOMContentLoaded', function () {
    setupEventListeners();
    calculateAll();
});

function setupEventListeners() {
    // Auto-calculation events
    document.getElementById('ticket_fee').addEventListener('input', calculateAll);
    document.querySelector('select[name="payment_method"]').addEventListener('change', calculateAll);
    document.getElementById('received_amount').addEventListener('input', calculateDue);

    // Clear errors when user types
    document.querySelectorAll('input, select').forEach(field => {
        field.addEventListener('input', () => clearFieldError(field));
    });

    // Review button click
    document.getElementById('reviewButton').addEventListener('click', handleReviewClick);
}

function calculateAll() {
    calculateTotalPayable();
    calculateDue();
}

// Calculate total payable with 2% extra for digital payments
function calculateTotalPayable() {
    const ticketFee = getNumberValue('ticket_fee');
    const paymentMethod = document.querySelector('select[name="payment_method"]').value;

    let totalPayable = ticketFee;

    if (paymentMethod === 'Bkash' || paymentMethod === 'Nagad') {
        const extraFee = ticketFee * 0.02;
        totalPayable = ticketFee + extraFee;
        showFeeMessage(`2% extra fee: ৳${extraFee.toFixed(2)}`);
    } else {
        hideFeeMessage();
    }

    setValue('total_payable', totalPayable);

    // Auto-fill received amount if empty
    const receivedAmount = getNumberValue('received_amount');
    if (!receivedAmount) {
        setValue('received_amount', totalPayable);
    }
}

// Calculate due amount
function calculateDue() {
    const totalPayable = getNumberValue('total_payable');
    const receivedAmount = getNumberValue('received_amount');
    const dueAmount = Math.max(0, totalPayable - receivedAmount);
    setValue('due_amount', dueAmount);
}

// Handle review button click
function handleReviewClick() {
    clearAllErrors();

    if (!isFormValid()) return; // Stop if form invalid

    const customerMobile = getField('customer_mobile').value.trim();
    const journeyDate = getField('journey_date').value;

    // Call Laravel route to check duplicate
    fetch('/ship-ticket-sales/check-duplicate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            customer_mobile: customerMobile,
            journey_date: journeyDate
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.exists) {
                // Show warning alert
                if (confirm(`${data.message}\n\nThis ticket was bought within 24 hours. Do you want to continue?`)) {
                    showReviewModal(); // User confirmed, show review
                } else {
                    // User cancelled
                    return;
                }
            } else {
                // No duplicate, proceed
                showReviewModal();
            }
        })
        .catch(err => {
            console.error(err);
            alert('Something went wrong while checking duplicates.');
        });
}


// Check if form is valid
function isFormValid() {
    const requiredFields = [
        'customer_name',
        'customer_mobile',
        'ship_id',
        'journey_date',
        'ticket_fee',
        'payment_method',
        'received_amount',
        'company_id',
        'issued_date',
        'sold_by'
    ];

    let isValid = true;
    let firstErrorField = null;

    // Check required fields
    requiredFields.forEach(fieldName => {
        const field = getField(fieldName);
        const value = field.value.trim();

        if (!value) {
            showFieldError(field, `${getFieldLabel(fieldName)} is required`);
            isValid = false;
            if (!firstErrorField) firstErrorField = field;
        }
    });

    // Check mobile format
    const mobileField = getField('customer_mobile');
    if (mobileField.value.trim() && !isValidMobile(mobileField.value)) {
        showFieldError(mobileField, 'Enter valid mobile (01XXXXXXXXX)');
        isValid = false;
        if (!firstErrorField) firstErrorField = mobileField;
    }

    // Check amounts are positive
    const ticketFee = getNumberValue('ticket_fee');
    const receivedAmount = getNumberValue('received_amount');

    if (ticketFee <= 0) {
        showFieldError(getField('ticket_fee'), 'Ticket fee must be greater than 0');
        isValid = false;
        if (!firstErrorField) firstErrorField = getField('ticket_fee');
    }

    if (receivedAmount <= 0) {
        showFieldError(getField('received_amount'), 'Received amount must be greater than 0');
        isValid = false;
        if (!firstErrorField) firstErrorField = getField('received_amount');
    }

    // Scroll to first error
    if (firstErrorField) {
        firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstErrorField.focus();
        showTopError('Please fix the errors below');
    }

    return isValid;
}



// Show review modal
function showReviewModal() {
    fillReviewContent();

    // Show modal
    const modal = document.getElementById('reviewModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    // Close modal when clicking outside or close button
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    document.getElementById('modalBackdrop').addEventListener('click', closeModal);

    //edit btn
    document.getElementById('editInfoButton').addEventListener('click', () => {
        closeModal(); // hide modal
        document.getElementById('ticketForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.getElementById('ticketForm').querySelector('input, select').focus();
    });
    document.querySelectorAll('[data-modal-hide="reviewModal"]').forEach(btn => {
        btn.addEventListener('click', closeModal);
    });
}

// Close modal
function closeModal() {
    const modal = document.getElementById('reviewModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Fill review modal content
function fillReviewContent() {
    const formData = new FormData(document.getElementById('ticketForm'));
    const fieldLabels = {
        'customer_name': 'Customer Name',
        'customer_mobile': 'Mobile Number',
        'sales_source': 'Sales Source',
        'ship_id': 'Ship Name',
        'journey_date': 'Journey Date',
        'company_id': 'Company Name',
        'ticket_fee': 'Ticket Fee',
        'payment_method': 'Payment Method',
        'total_payable': 'Total Payable',
        'received_amount': 'Received Amount',
        'due_amount': 'Due Amount',
        'issued_date': 'Issued Date',
        'sold_by': 'Sold By'
    };

    let html = '<div class="grid grid-cols-3 gap-4">';

    for (const [field, label] of Object.entries(fieldLabels)) {
        let value = formData.get(field) || 'Not specified';

        // Format currency
        if (['ticket_fee', 'total_payable', 'received_amount', 'due_amount'].includes(field)) {
            value = '৳ ' + parseFloat(value || 0).toFixed(2);
        }

        // Format dates
        if (['journey_date', 'issued_date'].includes(field) && value !== 'Not specified') {
            value = new Date(value).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        html += `
            <div class="border-b border-gray-100 dark:border-gray-700 pb-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">${label}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-medium">${value}</dd>
            </div>`;
    }

    html += '</div>';
    document.getElementById('reviewContent').innerHTML = html;
}

// Helper functions
function getField(name) {
    return document.querySelector(`[name="${name}"]`);
}

function getNumberValue(id) {
    return parseFloat(document.getElementById(id).value) || 0;
}

function setValue(id, value) {
    document.getElementById(id).value = value.toFixed(2);
}

function getFieldLabel(fieldName) {
    const labels = {
        'customer_name': 'Customer Name',
        'customer_mobile': 'Mobile Number',
        'ship_id': 'Ship Name',
        'journey_date': 'Journey Date',
        'ticket_fee': 'Ticket Fee',
        'payment_method': 'Payment Method',
        'received_amount': 'Received Amount',
        'company_id': 'Company Name',
        'issued_date': 'Issued Date',
        'sold_by': 'Sold By'
    };
    return labels[fieldName] || fieldName;
}

function isValidMobile(mobile) {
    return /^01[2-9]\d{8}$/.test(mobile);
}

// Error handling functions
function showFieldError(field, message) {
    field.classList.add('border-red-500', 'dark:border-red-500', 'focus:ring-red-500');
    field.classList.remove('border-gray-300', 'dark:border-gray-600', 'focus:ring-blue-500');

    const errorDiv = document.createElement('div');
    errorDiv.className = 'text-red-600 dark:text-red-400 text-sm mt-1 flex items-center';
    errorDiv.innerHTML = `
        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        ${message}
    `;

    field.parentNode.appendChild(errorDiv);
    field._errorElement = errorDiv;
}

function clearFieldError(field) {
    field.classList.remove('border-red-500', 'dark:border-red-500', 'focus:ring-red-500');
    field.classList.add('border-gray-300', 'dark:border-gray-600', 'focus:ring-blue-500');

    if (field._errorElement) {
        field._errorElement.remove();
        field._errorElement = null;
    }
}

function clearAllErrors() {
    // Clear top error
    const topError = document.getElementById('topValidationError');
    if (topError) topError.remove();

    // Clear field errors
    document.querySelectorAll('input, select').forEach(clearFieldError);
}

function showTopError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.id = 'topValidationError';
    errorDiv.className = 'bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4 flex items-center';
    errorDiv.innerHTML = `
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        ${message}
    `;

    document.querySelector('h1').insertAdjacentElement('afterend', errorDiv);
}

// Fee message functions
function showFeeMessage(message) {
    let feeMessage = document.getElementById('feeMessage');
    if (!feeMessage) {
        feeMessage = document.createElement('div');
        feeMessage.id = 'feeMessage';
        feeMessage.className = 'text-sm text-blue-600 dark:text-blue-400 mt-1 flex items-center';
        document.querySelector('select[name="payment_method"]').parentNode.appendChild(feeMessage);
    }

    feeMessage.innerHTML = `
        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        ${message}
    `;
}

function hideFeeMessage() {
    const feeMessage = document.getElementById('feeMessage');
    if (feeMessage) feeMessage.remove();
}