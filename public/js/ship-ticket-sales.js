document.addEventListener("DOMContentLoaded", function () {
    setupEventListeners();
    calculateAll();
    toggleReturnJourneySection();
});

function setupEventListeners() {
    // Payment calculation listeners
    document.getElementById("ticket_fee").addEventListener("input", calculateAll);
    document.getElementById("payment_method").addEventListener("change", calculateAll);
    document.getElementById("received_amount").addEventListener("input", calculateDue);
    
    // Form validation listeners
    document.querySelectorAll("input, select").forEach((field) => {
        field.addEventListener("input", () => clearFieldError(field));
    });

    // Main action listeners
    document.getElementById("reviewButton").addEventListener("click", handleReviewClick);
    
    // Ship and journey listeners
    document.getElementById("ship_id").addEventListener("change", loadTicketCategories);
    document.getElementById("return_date").addEventListener("change", toggleReturnJourneySection);
    
    // Initialize co-passenger functionality
    initializeCoPassenger();
}

function toggleReturnJourneySection() {
    const returnDate = document.getElementById("return_date").value;
    const returnSection = document.getElementById("returnJourneySection");
    const noReturnMessage = document.getElementById("noReturnCategoriesMessage");
    
    if (returnDate) {
        returnSection.style.display = 'block';
        noReturnMessage.classList.add("hidden");
        loadTicketCategories();
    } else {
        returnSection.style.display = 'none';
        document.getElementById("returnTicketCategoriesContainer").innerHTML = '';
        noReturnMessage.classList.remove("hidden");
        calculateTotalTickets();
    }
}

function loadTicketCategories() {
    const shipId = document.getElementById("ship_id").value;
    const returnDate = document.getElementById("return_date").value;
    const departureContainer = document.getElementById("departureTicketCategoriesContainer");
    const returnContainer = document.getElementById("returnTicketCategoriesContainer");
    const noDepartureMessage = document.getElementById("noDepartureCategoriesMessage");
    const noReturnMessage = document.getElementById("noReturnCategoriesMessage");

    if (!shipId) {
        departureContainer.innerHTML = '';
        returnContainer.innerHTML = '';
        noDepartureMessage.classList.remove("hidden");
        noReturnMessage.classList.remove("hidden");
        return;
    }

    // Show loading message
    departureContainer.innerHTML = '<div class="text-gray-500 dark:text-gray-400">Loading categories...</div>';
    returnContainer.innerHTML = '<div class="text-gray-500 dark:text-gray-400">Loading categories...</div>';
    noDepartureMessage.classList.add("hidden");
    noReturnMessage.classList.add("hidden");

    fetch(`/ship-packages/${shipId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }
            return response.json();
        })
        .then(packages => {
            // Clear containers
            departureContainer.innerHTML = '';
            returnContainer.innerHTML = '';
            
            if (packages && packages.length > 0) {
                // Create departure journey tickets
                packages.forEach((pkg, index) => {
                    const departureDiv = createCategoryField(pkg, index, 'departure');
                    departureContainer.appendChild(departureDiv);
                });

                // Create return journey tickets only if return date is selected
                if (returnDate) {
                    packages.forEach((pkg, index) => {
                        const returnDiv = createCategoryField(pkg, index, 'return');
                        returnContainer.appendChild(returnDiv);
                    });
                    noReturnMessage.classList.add("hidden");
                } else {
                    noReturnMessage.classList.remove("hidden");
                }
                
                // Add event listeners to quantity inputs
                document.querySelectorAll('.ticket-quantity').forEach(input => {
                    input.addEventListener('input', calculateTotalTickets);
                });
                
                calculateTotalTickets();
            } else {
                departureContainer.innerHTML = '<div class="text-gray-500 dark:text-gray-400">No ticket categories available for this ship.</div>';
                returnContainer.innerHTML = '<div class="text-gray-500 dark:text-gray-400">No ticket categories available for this ship.</div>';
            }
        })
        .catch(error => {
            console.error("Error fetching packages:", error);
            departureContainer.innerHTML = '<div class="text-red-500 dark:text-red-400">Error loading ticket categories</div>';
            returnContainer.innerHTML = '<div class="text-red-500 dark:text-red-400">Error loading ticket categories</div>';
        });
}

function createCategoryField(pkg, index, type) {
    const div = document.createElement("div");
    div.classList.add("grid", "grid-cols-2", "gap-4", "items-end");
    
    div.innerHTML = `
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                ${pkg.name || 'Unnamed Category'} (${type === 'departure' ? 'Departure' : 'Return'})
            </label>
            <input type="hidden" name="ticket_categories[${type}][${index}][name]" value="${pkg.name || ''}">
            <input type="hidden" name="ticket_categories[${type}][${index}][package_id]" value="${pkg.id || ''}">
            <p class="text-sm text-gray-600 dark:text-gray-400">Category: ${pkg.name || 'Unnamed'}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Quantity
            </label>
            <input type="number" 
                name="ticket_categories[${type}][${index}][quantity]" 
                value="0" 
                min="0" 
                class="ticket-quantity w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition"
                data-type="${type}">
        </div>
    `;
    
    return div;
}

function calculateTotalTickets() {
    let totalTickets = 0;
    
    // Calculate from quantity inputs
    document.querySelectorAll('.ticket-quantity').forEach(input => {
        const quantity = parseInt(input.value) || 0;
        totalTickets += quantity;
    });
    
    // Update the total tickets field
    document.getElementById("total_tickets").value = totalTickets;
}

function calculateAll() {
    calculateTotalPayable();
    calculateDue();
}

function calculateTotalPayable() {
    const ticketFee = parseFloat(document.getElementById("ticket_fee").value) || 0;
    const paymentMethod = document.getElementById("payment_method").value;

    let totalPayable = ticketFee;

    if (paymentMethod === "Bkash" || paymentMethod === "Nagad") {
        const extraFee = ticketFee * 0.02;
        totalPayable = ticketFee + extraFee;
        showFeeMessage(`2% extra fee: ৳${extraFee.toFixed(2)}`);
    } else {
        hideFeeMessage();
    }

    document.getElementById("total_payable").value = totalPayable.toFixed(2);
}

function calculateDue() {
    const totalPayable = parseFloat(document.getElementById("total_payable").value) || 0;
    const receivedAmount = parseFloat(document.getElementById("received_amount").value) || 0;
    const dueAmount = Math.max(0, totalPayable - receivedAmount);
    document.getElementById("due_amount").value = dueAmount.toFixed(2);
}

function handleReviewClick() {
    clearAllErrors();

    if (!isFormValid()) return;

    const customerMobile = document.querySelector('[name="customer_mobile"]').value.trim();
    const journeyDate = document.querySelector('[name="journey_date"]').value;

    fetch("/ship-ticket-sales/check-duplicate", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
        },
        body: JSON.stringify({
            customer_mobile: customerMobile,
            journey_date: journeyDate,
        }),
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.exists) {
                Swal.fire({
                    title: `${data.message}`,
                    text: "This ticket was bought within 24 hours. Do you want to continue?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, continue",
                    cancelButtonText: "Cancel",
                    customClass: {
                        confirmButton: "bg-blue-600 text-white",
                        cancelButton: "bg-red-500 text-white",
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        showReviewModal();
                    }
                });
            } else {
                showReviewModal();
            }
        })
        .catch((err) => {
            console.error(err);
            alert("Something went wrong while checking duplicates.");
        });
}

function isFormValid() {
    const requiredFields = [
        "customer_name",
        "customer_mobile",
        "date_of_birth",
        "address",
        "ship_id",
        "nid",
        "email",
        "journey_date",
        "ticket_fee",
        "payment_method",
        "received_amount",
        "company_id",
        "issued_date",
        "sold_by",
    ];

    let isValid = true;
    let firstErrorField = null;

    // Check required fields
    requiredFields.forEach((fieldName) => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        const value = field.value.trim();

        if (!value) {
            showFieldError(field, `${getFieldLabel(fieldName)} is required`);
            isValid = false;
            if (!firstErrorField) firstErrorField = field;
        }
    });

    // Check mobile format
    const mobileField = document.querySelector('[name="customer_mobile"]');
    if (mobileField.value.trim() && !isValidMobile(mobileField.value)) {
        showFieldError(mobileField, "Enter valid mobile (01XXXXXXXXX)");
        isValid = false;
        if (!firstErrorField) firstErrorField = mobileField;
    }

    // Check amounts are positive
    const ticketFee = parseFloat(document.getElementById("ticket_fee").value) || 0;
    const receivedAmount = parseFloat(document.getElementById("received_amount").value) || 0;

    if (ticketFee <= 0) {
        showFieldError(
            document.getElementById("ticket_fee"),
            "Ticket fee must be greater than 0"
        );
        isValid = false;
        if (!firstErrorField) firstErrorField = document.getElementById("ticket_fee");
    }

    if (receivedAmount <= 0) {
        showFieldError(
            document.getElementById("received_amount"),
            "Received amount must be greater than 0"
        );
        isValid = false;
        if (!firstErrorField) firstErrorField = document.getElementById("received_amount");
    }

    // Check if at least one ticket is selected
    const totalTickets = parseInt(document.getElementById("total_tickets").value) || 0;
    if (totalTickets <= 0) {
        showTopError("Please select at least one ticket");
        isValid = false;
    }

    // Scroll to first error
    if (firstErrorField) {
        firstErrorField.scrollIntoView({ behavior: "smooth", block: "center" });
        firstErrorField.focus();
        showTopError("Please fix the errors below");
    }

    return isValid;
}

function showReviewModal() {
    fillReviewContent();

    const modal = document.getElementById("reviewModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");

    modal.addEventListener("click", function (e) {
        if (e.target === modal) closeModal();
    });
    document.getElementById("modalBackdrop").addEventListener("click", closeModal);

    document.getElementById("editInfoButton").addEventListener("click", closeModal);
    document.querySelectorAll('[data-modal-hide="reviewModal"]').forEach((btn) => {
        btn.addEventListener("click", closeModal);
    });
}

function closeModal() {
    const modal = document.getElementById("reviewModal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
}

function fillReviewContent() {
    const formData = new FormData(document.getElementById("ticketForm"));
    const fieldLabels = {
        customer_name: "Customer Name",
        customer_mobile: "Mobile Number",
        date_of_birth: "Date Of Birth",
        nid: "NID",
        email: "Email",
        address: "Full Address",
        sales_source: "Sales Source",
        ship_id: "Ship Name",
        journey_date: "Journey Date",
        return_date: "Return Date",
        company_id: "Company Name",
        ticket_fee: "Total Ticket Price",
        payment_method: "Payment Method",
        total_payable: "Total Payable",
        received_amount: "Received Amount",
        due_amount: "Due Amount",
        issued_date: "Issued Date",
        sold_by: "Sold By",
        total_tickets: "Total Tickets",
    };

    let html = '<div class="grid grid-cols-2 gap-4">';

    // Loop through normal fields
    for (const [field, label] of Object.entries(fieldLabels)) {
        let value = formData.get(field) || "Not specified";

        if (field === "ship_id") {
            const select = document.querySelector('select[name="ship_id"]');
            value = select?.options[select.selectedIndex]?.text || "Not specified";
        }

        if (field === "company_id") {
            const select = document.querySelector('select[name="company_id"]');
            value = select?.options[select.selectedIndex]?.text || "Not specified";
        }

        // Currency formatting
        if (["ticket_fee", "total_payable", "received_amount", "due_amount"].includes(field)) {
            value = "৳ " + (parseFloat(value) || 0).toFixed(2);
        }

        // Date formatting
        if (["journey_date", "issued_date", "return_date", "date_of_birth"].includes(field) && value !== "Not specified") {
            const date = new Date(value);
            if (!isNaN(date)) {
                value = date.toLocaleDateString("en-US", {
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                });
            }
        }

        html += `
            <div class="border-b border-gray-100 dark:border-gray-700 pb-2">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">${label}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-medium">${value}</dd>
            </div>`;
    }

    html += "</div>";

    // Add ticket categories to review
    const departureTicketCategories = [];
    document.querySelectorAll('.ticket-quantity[data-type="departure"]').forEach(input => {
        const quantity = parseInt(input.value) || 0;
        if (quantity > 0) {
            const name = input.closest('div').previousElementSibling.querySelector('input[name$="[name]"]').value;
            departureTicketCategories.push({ name, quantity, type: 'Departure' });
        }
    });

    const returnTicketCategories = [];
    document.querySelectorAll('.ticket-quantity[data-type="return"]').forEach(input => {
        const quantity = parseInt(input.value) || 0;
        if (quantity > 0) {
            const name = input.closest('div').previousElementSibling.querySelector('input[name$="[name]"]').value;
            returnTicketCategories.push({ name, quantity, type: 'Return' });
        }
    });

    if (departureTicketCategories.length > 0 || returnTicketCategories.length > 0) {
        html += `
            <div class="mt-6">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-3 border-b pb-2">
                    Ticket Categories
                </h4>
                <div class="space-y-3">
        `;

        // Add departure journey tickets
        departureTicketCategories.forEach((category, index) => {
            html += `
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">${category.name}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Type: ${category.type}</p>
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Quantity: ${category.quantity}</p>
                </div>`;
        });

        // Add return journey tickets
        returnTicketCategories.forEach((category, index) => {
            html += `
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">${category.name}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Type: ${category.type}</p>
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Quantity: ${category.quantity}</p>
                </div>`;
        });

        html += `</div></div>`;
    }

    const coPassengerFields = [...document.querySelectorAll(".co-passenger")];
    if (coPassengerFields.length > 0) {
        html += `
            <div class="mt-6">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-3 border-b pb-2">
                    Co-Passenger Details
                </h4>
                <div class="space-y-3">
        `;

        coPassengerFields.forEach((group, index) => {
            const name = group.querySelector('input[name^="co_passengers"][name$="[name]"]')?.value?.trim() || "Not specified";
            const nid = group.querySelector('input[name^="co_passengers"][name$="[nid]"]')?.value?.trim() || "Not specified";

            html += `
                <div class="border-b border-gray-100 dark:border-gray-700 pb-2">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">#${index + 1}. ${name}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">NID: ${nid}</p>
                </div>`;
        });

        html += `</div></div>`;
    }

    document.getElementById("reviewContent").innerHTML = html;
}

// Helper functions
function getFieldLabel(fieldName) {
    const labels = {
        customer_name: "Customer Name",
        customer_mobile: "Mobile Number",
        ship_id: "Ship Name",
        journey_date: "Journey Date",
        ticket_fee: "Ticket Fee",
        payment_method: "Payment Method",
        received_amount: "Received Amount",
        company_id: "Company Name",
        issued_date: "Issued Date",
        sold_by: "Sold By",
        total_tickets: "Total Tickets",
    };
    return labels[fieldName] || fieldName;
}

function isValidMobile(mobile) {
    return /^01[2-9]\d{8}$/.test(mobile);
}

function showFieldError(field, message) {
    field.classList.add("border-red-500", "dark:border-red-500", "focus:ring-red-500");
    field.classList.remove("border-gray-300", "dark:border-gray-600", "focus:ring-blue-500");

    const errorDiv = document.createElement("div");
    errorDiv.className = "text-red-600 dark:text-red-400 text-sm mt-1 flex items-center";
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
    field.classList.remove("border-red-500", "dark:border-red-500", "focus:ring-red-500");
    field.classList.add("border-gray-300", "dark:border-gray-600", "focus:ring-blue-500");

    if (field._errorElement) {
        field._errorElement.remove();
        field._errorElement = null;
    }
}

function clearAllErrors() {
    const topError = document.getElementById("topValidationError");
    if (topError) topError.remove();
    document.querySelectorAll("input, select").forEach(clearFieldError);
}

function showTopError(message) {
    const errorDiv = document.createElement("div");
    errorDiv.id = "topValidationError";
    errorDiv.className = "bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4 flex items-center";
    errorDiv.innerHTML = `
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        ${message}
    `;

    document.querySelector("h1").insertAdjacentElement("afterend", errorDiv);
}

function showFeeMessage(message) {
    let feeMessage = document.getElementById("feeMessage");
    if (!feeMessage) {
        feeMessage = document.createElement("div");
        feeMessage.id = "feeMessage";
        feeMessage.className = "text-sm text-blue-600 dark:text-blue-400 mt-1 flex items-center";
        document.getElementById("payment_method").parentNode.appendChild(feeMessage);
    }

    feeMessage.innerHTML = `
        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        ${message}
    `;
}

function hideFeeMessage() {
    const feeMessage = document.getElementById("feeMessage");
    if (feeMessage) feeMessage.remove();
}

// Co-Passenger functionality
function initializeCoPassenger() {
    const wrapper = document.getElementById("coPassengersWrapper");
    const addBtn = document.getElementById("addCoPassengerBtn");

    let index = 0;

    addBtn.addEventListener("click", function () {
        const div = document.createElement("div");
        div.classList.add("co-passenger", "grid", "grid-cols-2", "gap-4", "p-4", "border", "border-gray-200", "dark:border-gray-700", "rounded-lg");

        div.innerHTML = `
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Co-Passenger Name
                </label>
                <input type="text" name="co_passengers[${index}][name]" placeholder="Enter co-passenger name"
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Co-Passenger NID
                    </label>
                    <input type="text" name="co_passengers[${index}][nid]" placeholder="Enter NID"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <button type="button" class="removeCoPassengerBtn px-3 py-2 text-red-600 hover:text-red-800 font-semibold transition">
                    Remove
                </button>
            </div>
        `;

        // Insert before the add button
        wrapper.insertBefore(div, addBtn);
        index++;
    });

    // Remove co-passenger field
    wrapper.addEventListener("click", function (e) {
        if (e.target.classList.contains("removeCoPassengerBtn")) {
            e.target.closest(".co-passenger").remove();
        }
    });
}