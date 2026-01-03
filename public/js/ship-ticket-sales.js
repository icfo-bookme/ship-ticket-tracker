class TicketSalesSystem {
    constructor() {
        this.currentPackages = [];
        this.paymentIndex = 0;
        this.coPassengerIndex = 0;
        this.init();
    }

    init() {
        document.addEventListener("DOMContentLoaded", () => {
            this.setupEventListeners();
            this.calculateAll();
            this.toggleReturnJourneySection();
        });
    }

    setupEventListeners() {
        try {
            // Payment calculation listeners
            this.addEventListener('ticket_fee', 'input', () => this.calculateAll());
            this.addEventListener('received_amount', 'input', () => this.calculateDue());
            this.addEventListener('other_fee', 'input', () => this.calculateAll());

            // Form validation listeners
            document.querySelectorAll("input, select").forEach((field) => {
                field.addEventListener("input", () => this.clearFieldError(field));
            });

            // Main action listeners
            this.addEventListener('reviewButton', 'click', () => this.handleReviewClick());

            // Ship and journey listeners
            this.addEventListener('ship_id', 'change', () => this.loadTicketCategories());
            this.addEventListener('return_date', 'change', () => this.toggleReturnJourneySection());

            // Mobile and WhatsApp listeners
            const mobileField = document.querySelector('[name="customer_mobile"]');
            mobileField.addEventListener('input', () => {
                const checkbox = document.getElementById("sameAsMobileCheckbox");
                if (checkbox.checked) this.handleSameAsMobileCheckbox();
            });
            this.addEventListener('sameAsMobileCheckbox', 'change', () => this.handleSameAsMobileCheckbox());

            // Initialize components
            this.initializeCoPassenger();
            this.initializePaymentMethod();

        } catch (error) {
            console.error('Error setting up event listeners:', error);
            this.showNotification('Error initializing form. Please refresh the page.', 'error');
        }
    }

    // Helper Methods
    addEventListener(elementId, event, callback) {
        const element = document.getElementById(elementId);
        if (element) {
            element.addEventListener(event, callback);
        } else {
            console.warn(`Element with ID '${elementId}' not found`);
        }
    }

    getElement(selector) {
        return document.querySelector(selector);
    }

    getValue(elementId) {
        const element = document.getElementById(elementId);
        return element ? element.value.trim() : '';
    }

    setValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) element.value = value;
    }

    // Mobile Number Handling
    handleSameAsMobileCheckbox() {
        const checkbox = document.getElementById("sameAsMobileCheckbox");
        const mobileField = document.querySelector('[name="customer_mobile"]');
        const whatsappField = document.querySelector('[name="whatsapp"]');

        if (checkbox.checked) {
            const mobileValue = mobileField.value.trim();

            if (!mobileValue) {
                this.showNotification("Please enter mobile number first", "warning");
                checkbox.checked = false;
                mobileField.focus();
                return;
            }

            if (!this.isValidMobile(mobileValue)) {
                this.showNotification("Please enter a valid mobile number first", "warning");
                checkbox.checked = false;
                mobileField.focus();
                return;
            }

            // Copy mobile to WhatsApp and disable the field
            whatsappField.value = mobileValue;
            whatsappField.classList.add('bg-gray-100', 'dark:bg-gray-800', 'text-gray-500', 'dark:text-gray-400');
            this.clearFieldError(whatsappField);
        } else {
            // Enable WhatsApp field and clear styling
            whatsappField.disabled = false;
            whatsappField.classList.remove('bg-gray-100', 'dark:bg-gray-800', 'text-gray-500', 'dark:text-gray-400');
            whatsappField.value = '';
        }
    }

    // Journey Section Management
    toggleReturnJourneySection() {
        const returnDate = this.getValue("return_date");
        const returnSection = document.getElementById("returnJourneySection");
        const noReturnMessage = document.getElementById("noReturnCategoriesMessage");

        if (returnDate) {
            returnSection.style.display = 'block';
            noReturnMessage.classList.add("hidden");
            this.loadTicketCategories();
        } else {
            returnSection.style.display = 'none';
            document.getElementById("returnTicketCategoriesContainer").innerHTML = '';
            noReturnMessage.classList.remove("hidden");
            this.calculateTotalTickets();
            this.calculateTicketFee();
        }
    }

    // Ticket Categories Management
    async loadTicketCategories() {
        const shipId = this.getValue("ship_id");
        const returnDate = this.getValue("return_date");
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

        // Show loading state
        this.showLoadingState(departureContainer, returnContainer);
        noDepartureMessage.classList.add("hidden");
        noReturnMessage.classList.add("hidden");

        try {
            const response = await fetch(`/ship-packages/${shipId}`);
            if (!response.ok) throw new Error(`Server error: ${response.status}`);

            const packages = await response.json();
            this.currentPackages = packages;
            this.renderTicketCategories(packages, returnDate);

        } catch (error) {
            console.error("Error fetching packages:", error);
            this.showErrorState(departureContainer, returnContainer);
        }
    }

    showLoadingState(departureContainer, returnContainer) {
        const loadingHTML = '<div class="text-gray-500 dark:text-gray-400">Loading categories...</div>';
        departureContainer.innerHTML = loadingHTML;
        returnContainer.innerHTML = loadingHTML;
    }

    showErrorState(departureContainer, returnContainer) {
        const errorHTML = '<div class="text-red-500 dark:text-red-400">Error loading ticket categories</div>';
        departureContainer.innerHTML = errorHTML;
        returnContainer.innerHTML = errorHTML;
    }

    renderTicketCategories(packages, returnDate) {
        const departureContainer = document.getElementById("departureTicketCategoriesContainer");
        const returnContainer = document.getElementById("returnTicketCategoriesContainer");
        const noDepartureMessage = document.getElementById("noDepartureCategoriesMessage");
        const noReturnMessage = document.getElementById("noReturnCategoriesMessage");

        departureContainer.innerHTML = '';
        returnContainer.innerHTML = '';

        if (packages?.length > 0) {
            // Create departure journey tickets
            packages.forEach((pkg, index) => {
                departureContainer.appendChild(this.createCategoryField(pkg, index, 'departure'));
            });

            // Create return journey tickets only if return date is selected
            if (returnDate) {
                packages.forEach((pkg, index) => {
                    returnContainer.appendChild(this.createCategoryField(pkg, index, 'return'));
                });
                noReturnMessage.classList.add("hidden");
            } else {
                noReturnMessage.classList.remove("hidden");
            }

            this.attachTicketQuantityListeners();
            this.calculateTotalTickets();
            this.calculateTicketFee();
        } else {
            this.showNoCategoriesMessage(departureContainer, returnContainer);
        }
    }

    showNoCategoriesMessage(departureContainer, returnContainer) {
        const messageHTML = '<div class="text-gray-500 dark:text-gray-400">No ticket categories available for this ship.</div>';
        departureContainer.innerHTML = messageHTML;
        returnContainer.innerHTML = messageHTML;
    }

    createCategoryField(pkg, index, type) {
        const div = document.createElement("div");
        div.classList.add("grid", "grid-cols-2", "gap-4", "items-end");

        const singlePrice = parseFloat(pkg.price) || 0;
        const roundTripPrice = parseFloat(pkg.round_trip_price) || 0;
        const returnTripPrice = roundTripPrice > 0 ? (roundTripPrice - singlePrice) : singlePrice;

        div.innerHTML = `
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ${pkg.name || 'Unnamed Category'} (${type === 'departure' ? 'Departure' : 'Return'})
                </label>
                <input type="hidden" name="ticket_categories[${type}][${index}][name]" value="${this.escapeHtml(pkg.name || '')}">
                <input type="hidden" name="ticket_categories[${type}][${index}][package_id]" value="${pkg.id || ''}">
                <input type="hidden" class="ticket-price" data-package-id="${pkg.id}" data-type="${type}" value="${singlePrice}">
                <input type="hidden" class="ticket-round-trip-price" data-package-id="${pkg.id}" data-type="${type}" value="${roundTripPrice}">
                <input type="hidden" class="ticket-return-price" data-package-id="${pkg.id}" data-type="${type}" value="${returnTripPrice}">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Category: ${this.escapeHtml(pkg.name || 'Unnamed')}<br>
                    ${type === 'departure' ?
                `Departure: ৳${singlePrice.toFixed(2)}` :
                `Return: ৳${returnTripPrice.toFixed(2)}`
            }
                    ${roundTripPrice > 0 ? `<br>Round trip: ৳${roundTripPrice.toFixed(2)}` : ''}
                </p>
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
                    data-package-id="${pkg.id}"
                    data-type="${type}">
            </div>
        `;
        return div;
    }

    attachTicketQuantityListeners() {
        document.querySelectorAll('.ticket-quantity').forEach(input => {
            input.addEventListener('input', () => {
                this.calculateTotalTickets();
                this.calculateTicketFee();
            });
        });
    }

    // Calculation Methods
    calculateTicketFee() {
        const hasReturnJourney = !!this.getValue("return_date");
        const totalTicketFee = hasReturnJourney ?
            this.calculateRoundTripPricing() :
            this.calculateSingleJourneyPricing();

        this.setValue("ticket_fee", totalTicketFee.toFixed(2));
        this.calculateTotalPayable();
    }

    calculateSingleJourneyPricing() {
        let total = 0;
        document.querySelectorAll('.ticket-quantity').forEach(input => {
            const quantity = parseInt(input.value) || 0;
            const packageId = input.getAttribute('data-package-id');

            if (quantity > 0) {
                const pkg = this.currentPackages.find(p => p.id == packageId);
                if (pkg) {
                    total += quantity * (parseFloat(pkg.price) || 0);
                }
            }
        });
        return total;
    }

    calculateRoundTripPricing() {
        const packageQuantities = this.groupQuantitiesByPackage();
        let total = this.calculateSameCategoryPricing(packageQuantities);
        total += this.calculateCrossCategoryPricing(packageQuantities);
        return total;
    }

    groupQuantitiesByPackage() {
        const quantities = {};
        document.querySelectorAll('.ticket-quantity').forEach(input => {
            const packageId = input.getAttribute('data-package-id');
            const type = input.getAttribute('data-type');
            const quantity = parseInt(input.value) || 0;

            if (!quantities[packageId]) {
                quantities[packageId] = { departure: 0, return: 0 };
            }
            quantities[packageId][type] = quantity;
        });
        return quantities;
    }

    calculateSameCategoryPricing(quantities) {
        let total = 0;
        Object.keys(quantities).forEach(packageId => {
            const pkg = this.currentPackages.find(p => p.id == packageId);
            if (!pkg) return;

            const { departure, return: returnQty } = quantities[packageId];
            const singlePrice = parseFloat(pkg.price) || 0;
            const roundTripPrice = parseFloat(pkg.round_trip_price) || 0;

            if (roundTripPrice > 0) {
                const roundTripPairs = Math.min(departure, returnQty);
                const remainingDeparture = departure - roundTripPairs;
                const remainingReturn = returnQty - roundTripPairs;

                total += (roundTripPairs * roundTripPrice);
                total += (remainingDeparture * singlePrice);
                total += (remainingReturn * singlePrice);
            } else {
                total += (departure * singlePrice);
                total += (returnQty * singlePrice);
            }
        });
        return total;
    }

    calculateCrossCategoryPricing(quantities) {
        const remainingQuantities = this.calculateRemainingQuantities(quantities);
        return this.calculateCrossCategoryPairs(remainingQuantities);
    }

    calculateRemainingQuantities(quantities) {
        const remaining = {};
        Object.keys(quantities).forEach(packageId => {
            const pkg = this.currentPackages.find(p => p.id == packageId);
            const { departure, return: returnQty } = quantities[packageId];

            if (pkg?.round_trip_price > 0) {
                const roundTripPairs = Math.min(departure, returnQty);
                remaining[packageId] = {
                    departure: departure - roundTripPairs,
                    return: returnQty - roundTripPairs,
                    singlePrice: parseFloat(pkg.price) || 0,
                    returnPrice: parseFloat(pkg.round_trip_price) - parseFloat(pkg.price) || 0
                };
            } else {
                remaining[packageId] = {
                    departure: departure,
                    return: returnQty,
                    singlePrice: parseFloat(pkg?.price) || 0,
                    returnPrice: parseFloat(pkg?.price) || 0
                };
            }
        });
        return remaining;
    }

    calculateCrossCategoryPairs(remainingQuantities) {
        let total = 0;
        const packageIds = Object.keys(remainingQuantities);

        packageIds.forEach(departureId => {
            const departureData = remainingQuantities[departureId];

            if (departureData.departure > 0) {
                packageIds.forEach(returnId => {
                    if (departureId !== returnId) {
                        const returnData = remainingQuantities[returnId];
                        const crossPairs = Math.min(departureData.departure, returnData.return);

                        if (crossPairs > 0) {
                            total += (crossPairs * departureData.singlePrice);
                            total += (crossPairs * returnData.returnPrice);

                            departureData.departure -= crossPairs;
                            returnData.return -= crossPairs;
                        }
                    }
                });
            }
        });

        // Add remaining individual tickets
        packageIds.forEach(packageId => {
            const data = remainingQuantities[packageId];
            total += (data.departure * data.singlePrice);
            total += (data.return * data.singlePrice);
        });

        return total;
    }

    calculateTotalTickets() {
        let total = 0;
        document.querySelectorAll('.ticket-quantity').forEach(input => {
            total += parseInt(input.value) || 0;
        });
        this.setValue("total_tickets", total);
    }

    calculateAll() {
        this.calculateTotalPayable();
        this.calculateDue();
    }

    calculateTotalPayable() {
        const ticketFee = parseFloat(this.getValue("ticket_fee")) || 0;
        const otherChargesFee = parseFloat(this.getValue("other_fee")) || 0;
        const totalPayable = ticketFee + otherChargesFee;
        this.setValue("total_payable", totalPayable.toFixed(2));
        this.calculateDue();
    }

    calculateDue() {
        const totalPayable = parseFloat(this.getValue("total_payable")) || 0;
        const receivedAmount = parseFloat(this.getValue("received_amount")) || 0;
        const dueAmount = Math.max(0, totalPayable - receivedAmount);
        this.setValue("due_amount", dueAmount.toFixed(2));
    }

    // Form Validation
    async handleReviewClick() {
        this.clearAllErrors();

        // Step-by-step validation with proper error focus
        const validationSteps = [
            () => this.validateRequiredFields(),
            () => this.validateMobileNumbers(),
            // () => this.validateTickets(),
            () => this.validateAmounts(),
            () => this.validatePaymentMethods() // Payment validation last
        ];

        for (let step of validationSteps) {
            const result = step();
            if (!result.isValid) {
                // Focus on the first error field
                if (result.firstErrorField) {
                    result.firstErrorField.focus();
                    result.firstErrorField.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
                return;
            }
        }

        try {
            const isDuplicate = await this.checkDuplicateTicket();
            console.log('Duplicate check result:', isDuplicate.exists);
            if (isDuplicate.exists) {
                this.showDuplicateWarning();
            } else {
                this.showReviewModal();
            }
        } catch (error) {
            console.error('Error checking duplicate:', error);
            this.showNotification('Something went wrong while checking duplicates.', 'error');
        }
    }

    async checkDuplicateTicket() {
        const customerMobile = document.querySelector('[name="customer_mobile"]').value.trim();
        const journeyDate = document.querySelector('[name="journey_date"]').value;

        const response = await fetch("/ship-ticket-sales/check-duplicate", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
            },
            body: JSON.stringify({ customer_mobile: customerMobile, journey_date: journeyDate }),
        });

        if (!response.ok) throw new Error('Network response was not ok');
        return await response.json();
    }

    showDuplicateWarning() {
        Swal.fire({
            title: 'Duplicate Ticket Found',
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
                this.showReviewModal();
            }
        });
    }

    validateRequiredFields() {
        const requiredFields = [
            { name: "customer_name", label: "Customer Name" },
            { name: "customer_mobile", label: "Mobile Number" },
            // { name: "date_of_birth", label: "Date of Birth" },
            // { name: "address", label: "Address" },
            { name: "ship_id", label: "Ship Name" },
            // { name: "nid", label: "NID" },
            // { name: "email", label: "Email" },
            // { name: "journey_date", label: "Journey Date" },
            { name: "company_id", label: "Company Name" },
            // { name: "issued_date", label: "Issued Date" },
            { name: "sold_by", label: "Sold By" },
            { name: "whatsapp", label: "WhatsApp Number" }
        ];

        let isValid = true;
        let firstErrorField = null;

        requiredFields.forEach(({ name, label }) => {
            const field = document.querySelector(`[name="${name}"]`);
            const value = field?.value?.toString().trim() || '';

            if (!value) {
                this.showFieldError(field, `${label} is required`);
                isValid = false;
                if (!firstErrorField) firstErrorField = field;
            }
        });

        return { isValid, firstErrorField };
    }

    validateMobileNumbers() {
        let isValid = true;
        let firstErrorField = null;

        const mobileField = document.querySelector('[name="customer_mobile"]');
        const mobileValue = mobileField.value.trim();

        if (mobileValue && !this.isValidMobile(mobileValue)) {
            this.showFieldError(mobileField, "Enter valid mobile number (01XXXXXXXXX)");
            isValid = false;
            firstErrorField = mobileField;
        }

        const whatsappField = document.querySelector('[name="whatsapp"]');
        const whatsappValue = whatsappField.value.trim();
        const isWhatsAppDisabled = whatsappField.disabled;

        if (!isWhatsAppDisabled && whatsappValue && !this.isValidMobile(whatsappValue)) {
            this.showFieldError(whatsappField, "Enter valid WhatsApp number (01XXXXXXXXX)");
            isValid = false;
            if (!firstErrorField) firstErrorField = whatsappField;
        }

        return { isValid, firstErrorField };
    }

    validateAmounts() {
        let isValid = true;
        let firstErrorField = null;

        const ticketFee = parseFloat(this.getValue("ticket_fee")) || 0;
        const receivedAmount = parseFloat(this.getValue("received_amount")) || 0;

        if (ticketFee <= 0) {
            this.showFieldError(document.getElementById("ticket_fee"), "Ticket fee must be greater than 0");
            isValid = false;
            firstErrorField = document.getElementById("ticket_fee");
        }

        if (receivedAmount <= 0) {
            this.showFieldError(document.getElementById("received_amount"), "Received amount must be greater than 0");
            isValid = false;
            if (!firstErrorField) firstErrorField = document.getElementById("received_amount");
        }

        return { isValid, firstErrorField };
    }

    // validateTickets() {
    //     const totalTickets = parseInt(this.getValue("total_tickets")) || 0;
    //     if (totalTickets <= 0) {
    //         this.showTopError("Please select at least one ticket");
    //         return { isValid: false, firstErrorField: null };
    //     }
    //     return { isValid: true, firstErrorField: null };
    // }

    validatePaymentMethods() {
        const paymentEntries = document.querySelectorAll('.payment-entry');
        let hasValidPayment = false;

        paymentEntries.forEach(entry => {
            const method = entry.querySelector('.payment-method-select').value;
            const amount = parseFloat(entry.querySelector('.payment-amount-input').value) || 0;

            if (method && amount > 0) {
                hasValidPayment = true;
            }
        });

        if (!hasValidPayment) {
            this.showTopError("Please add at least one valid payment method with amount");
            return { isValid: false, firstErrorField: null };
        }

        return { isValid: true, firstErrorField: null };
    }

    // Review Modal
    showReviewModal() {
        this.fillReviewContent();

        const modal = document.getElementById("reviewModal");
        modal.classList.remove("hidden");
        modal.classList.add("flex");

        modal.addEventListener("click", (e) => {
            if (e.target === modal || e.target.id === "modalBackdrop") this.closeModal();
        });

        document.getElementById("editInfoButton").addEventListener("click", () => this.closeModal());
        document.querySelectorAll('[data-modal-hide="reviewModal"]').forEach((btn) => {
            btn.addEventListener("click", () => this.closeModal());
        });
    }

    closeModal() {
        const modal = document.getElementById("reviewModal");
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }

    fillReviewContent() {
        const formData = new FormData(document.getElementById("ticketForm"));
        let html = this.generateReviewContent(formData);
        document.getElementById("reviewContent").innerHTML = html;
    }

    generateReviewContent(formData) {
        let html = '<div class="grid grid-cols-3 gap-4">';
        html += this.generateBasicInfo(formData);
        html += '</div>';
        html += this.generatePaymentMethods();
        html += this.generateTicketCategories();
        html += this.generateCoPassengerInfo();
        return html;
    }

    generateBasicInfo(formData) {
        const fieldLabels = {
            customer_name: "Customer Name",
            customer_mobile: "Mobile Number",
            whatsapp: "Whatsapp Number",
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
            total_payable: "Total Payable",
            received_amount: "Received Amount",
            due_amount: "Due Amount",
            bftn_status: "BFTN Status",
            issued_date: "Issued Date",
            sold_by: "Sold By",
            number_of_ticket: "Total Tickets",
            remark1: "Remark 1",
            remark2: "Remark 2",
        };

        let content = '';
        for (const [field, label] of Object.entries(fieldLabels)) {
            let value = this.formatReviewValue(field, formData.get(field));
            content += `
                <div class="border-b border-gray-100 dark:border-gray-700 pb-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">${label}</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white font-medium">${value}</dd>
                </div>`;
        }
        return content;
    }

    formatReviewValue(field, value) {
        if (!value) return "Not specified";

        // Handle select fields
        if (field === "ship_id" || field === "company_id") {
            const select = document.querySelector(`select[name="${field}"]`);
            return select?.options[select.selectedIndex]?.text || "Not specified";
        }

        // Currency formatting
        if (["ticket_fee", "total_payable", "received_amount", "due_amount"].includes(field)) {
            return "৳ " + (parseFloat(value) || 0).toFixed(2);
        }

        // Date formatting
        if (["journey_date", "issued_date", "return_date", "date_of_birth"].includes(field)) {
            const date = new Date(value);
            if (!isNaN(date)) {
                return date.toLocaleDateString("en-US", {
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                });
            }
        }

        return this.escapeHtml(value);
    }

    generatePaymentMethods() {
        const paymentMethods = [];
        document.querySelectorAll('.payment-entry').forEach(entry => {
            const method = entry.querySelector('.payment-method-select').value;
            const amount = parseFloat(entry.querySelector('.payment-amount-input').value) || 0;
            if (method && amount > 0) paymentMethods.push({ method, amount });
        });

        if (paymentMethods.length === 0) return '';

        let html = `
            <div class="mt-6">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-3 border-b pb-2">
                    Payment Methods
                </h4>
                <div class="space-y-3">
        `;

        paymentMethods.forEach(payment => {
            html += `
                <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">${payment.method}</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">৳ ${payment.amount.toFixed(2)}</p>
                </div>`;
        });

        html += `</div></div>`;
        return html;
    }

    generateTicketCategories() {
        const departureTickets = this.getTicketCategories('departure');
        const returnTickets = this.getTicketCategories('return');

        if (departureTickets.length === 0 && returnTickets.length === 0) return '';

        let html = `
            <div class="mt-6">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-3 border-b pb-2">
                    Ticket Categories
                </h4>
                <div class="space-y-3">
        `;

        [...departureTickets, ...returnTickets].forEach(category => {
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
        return html;
    }

    getTicketCategories(type) {
        const categories = [];
        document.querySelectorAll(`.ticket-quantity[data-type="${type}"]`).forEach(input => {
            const quantity = parseInt(input.value) || 0;
            if (quantity > 0) {
                const name = input.closest('div').previousElementSibling.querySelector('input[name$="[name]"]').value;
                categories.push({ name, quantity, type: type === 'departure' ? 'Departure' : 'Return' });
            }
        });
        return categories;
    }

    generateCoPassengerInfo() {
        const coPassengerFields = [...document.querySelectorAll(".co-passenger")];
        if (coPassengerFields.length === 0) return '';

        let html = `
            <div class="mt-6">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-white mb-3 border-b pb-2">
                    Co-Passenger Details
                </h4>
                <div class="space-y-3">
        `;

        coPassengerFields.forEach((group, index) => {
            const name = group.querySelector('input[name^="co_passengers"][name$="[name]"]')?.value?.trim() || "Not specified";
            const nid = group.querySelector('input[name^="co_passengers"][name$="[nid]"]')?.value?.trim() || "Not specified";
            const number = group.querySelector('input[name^="co_passengers"][name$="[co_passernger_number]"]')?.value?.trim() || "Not specified";

            html += `
                <div class="border-b flex item-center gap-5 border-gray-100 dark:border-gray-700 pb-2">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">#${index + 1}. ${this.escapeHtml(name)}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">NID: ${this.escapeHtml(nid)}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Number: ${this.escapeHtml(number)}</p>
                </div>`;
        });

        html += `</div></div>`;
        return html;
    }

    // Co-Passenger Management
    initializeCoPassenger() {
        const wrapper = document.getElementById("coPassengersWrapper");
        const addBtn = document.getElementById("addCoPassengerBtn");

        addBtn.addEventListener("click", () => this.addCoPassengerField(wrapper));

        wrapper.addEventListener("click", (e) => {
            if (e.target.classList.contains("removeCoPassengerBtn")) {
                e.target.closest(".co-passenger").remove();
            }
        });
    }

    addCoPassengerField(wrapper) {
        const div = document.createElement("div");
        div.classList.add("co-passenger", "grid", "grid-cols-3", "gap-4", "p-4", "border", "border-gray-200", "dark:border-gray-700", "rounded-lg");

        div.innerHTML = `
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Co-Passenger Name
                </label>
                <input type="text" name="co_passengers[${this.coPassengerIndex}][name]" placeholder="Enter co-passenger name"
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition">
            </div>

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Co-Passenger NID
                    </label>
                    <input type="text" name="co_passengers[${this.coPassengerIndex}][nid]" placeholder="Enter NID"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition">
                </div>
            </div>

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Co-Passenger Mobile Number
                    </label>
                    <input type="text" name="co_passengers[${this.coPassengerIndex}][co_passernger_number]" placeholder="Enter Mobile Number"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <button type="button" class="removeCoPassengerBtn fa-solid fa-trash px-3 py-2 text-red-600 hover:text-red-800 font-semibold transition">
                    
                </button>
            </div>
        `;

        wrapper.insertBefore(div, document.getElementById("addCoPassengerBtn"));
        this.coPassengerIndex++;
    }

    // Payment Method Management
    initializePaymentMethod() {
        const wrapper = document.getElementById("paymentInfoWrapper");
        const addBtn = document.getElementById("addPaymentInfo");

        addBtn.addEventListener("click", () => this.addPaymentEntry(wrapper));
        this.addPaymentEntry(wrapper); // Add initial payment entry
    }

    addPaymentEntry(wrapper) {
        const div = document.createElement("div");
        div.classList.add("payment-entry", "grid", "grid-cols-7", "gap-4", "p-4", "border", "border-gray-200", "dark:border-gray-700", "rounded-lg", "bg-white", "dark:bg-gray-800");

        div.innerHTML = `
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Payment Method <span class="text-red-500">*</span>
                </label>
                <select name="payment_methods[${this.paymentIndex}][method]" 
                    class="payment-method-select w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition">
                    <option value="">Select method</option>
                    <option value="Cash">Cash</option>
                    <option value="Bkash">Bkash</option>
                    <option value="Nagad">Nagad</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                   Receive Amount (৳) <span class="text-red-500">*</span>
                </label>
                <input type="number" name="payment_methods[${this.paymentIndex}][amount]" 
                    placeholder="Enter amount"
                    class="payment-amount-input w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition"
                    step="0.01" min="0" value="0">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                   Date <span class="text-red-500">*</span>
                </label>
                <input type="date"
                    name="payment_methods[${this.paymentIndex}][paid_date]"
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition"
                    value="${new Date().toISOString().slice(0, 10)}">
            </div>

            <div class="flex items-end col-span-1">
                <button type="button" class="removePaymentBtn fa-solid fa-trash w-full px-3 py-2 text-red-600 hover:text-red-800 font-semibold transition  hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 rounded-lg">
                </button>
            </div>
        `;

        wrapper.insertBefore(div, document.getElementById("addPaymentInfo"));
        this.attachPaymentEventListeners(div);
        this.paymentIndex++;
    }

    attachPaymentEventListeners(div) {
        const amountInput = div.querySelector('.payment-amount-input');
        const methodSelect = div.querySelector('.payment-method-select');
        const removeBtn = div.querySelector('.removePaymentBtn');

        amountInput.addEventListener('input', () => {
            this.calculatePaymentTotals();
            this.calculateTotalPayable();
        });

        methodSelect.addEventListener('change', () => {
            this.calculateTotalPayable();
        });

        removeBtn.addEventListener('click', () => {
            div.remove();
            this.calculatePaymentTotals();
            this.calculateTotalPayable();
        });
    }

    calculatePaymentTotals() {
        let totalReceived = 0;
        document.querySelectorAll('.payment-amount-input').forEach(input => {
            totalReceived += parseFloat(input.value) || 0;
        });
        this.setValue("received_amount", totalReceived.toFixed(2));
        this.calculateDue();
    }

    // Utility Methods
    isValidMobile(mobile) {
        return /^01[2-9]\d{8}$/.test(mobile);
    }

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    getFieldLabel(fieldName) {
        const labels = {
            customer_name: "Customer Name",
            customer_mobile: "Mobile Number",
            ship_id: "Ship Name",
            journey_date: "Journey Date",
            ticket_fee: "Ticket Fee",
            received_amount: "Received Amount",
            company_id: "Company Name",
            issued_date: "Issued Date",
            sold_by: "Sold By",
            total_tickets: "Total Tickets",
        };
        return labels[fieldName] || fieldName;
    }

    // Error Handling
    showFieldError(field, message) {
        // Remove any existing error
        this.clearFieldError(field);

        // Add error styling to field
        field.classList.add("border-red-500", "dark:border-red-500", "focus:ring-red-500", "focus:border-red-500");
        field.classList.remove("border-gray-300", "dark:border-gray-600", "focus:ring-blue-500", "focus:border-blue-500");

        // Create error message element
        const errorDiv = document.createElement("div");
        errorDiv.className = "text-red-600 dark:text-red-400 text-sm mt-1 flex items-start animate-fadeIn";
        errorDiv.innerHTML = `
            <svg class="w-4 h-4 mr-1 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            ${message}
        `;

        // Add error message after the field
        const parent = field.parentNode;
        parent.appendChild(errorDiv);
        field._errorElement = errorDiv;
    }

    clearFieldError(field) {
        field.classList.remove("border-red-500", "dark:border-red-500", "focus:ring-red-500", "focus:border-red-500");
        field.classList.add("border-gray-300", "dark:border-gray-600", "focus:ring-blue-500", "focus:border-blue-500");

        if (field._errorElement) {
            field._errorElement.remove();
            field._errorElement = null;
        }
    }

    clearAllErrors() {
        const topError = document.getElementById("topValidationError");
        if (topError) topError.remove();
        document.querySelectorAll("input, select").forEach(field => this.clearFieldError(field));
    }

    showTopError(message) {
        this.clearAllErrors();

        const errorDiv = document.createElement("div");
        errorDiv.id = "topValidationError";
        errorDiv.className = `
            fixed top-4 left-1/2 transform -translate-x-1/2
            bg-red-50 border border-red-200 text-red-800 px-6 py-4 
            rounded-lg shadow-lg flex items-center z-50 animate-fadeIn
            dark:bg-red-900/20 dark:border-red-800 dark:text-red-200
        `;
        errorDiv.innerHTML = `
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <span class="font-medium">${message}</span>
        `;
        document.body.appendChild(errorDiv);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }

    showNotification(message, type = 'info') {
        const colors = {
            success: 'green',
            error: 'red',
            warning: 'yellow',
            info: 'blue'
        };

        Swal.fire({
            title: message,
            icon: type,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: '#1f2937',
            color: 'white'
        });
    }
}

// Initialize the application
const ticketSystem = new TicketSalesSystem();