class TicketSalesSystem {
    constructor() {
        this.currentPackages = [];
        this.paymentIndex = 0;
        this.coPassengerIndex = 0;
        this.init();
    }

    init() {
        if (document.readyState === 'loading') {
            document.addEventListener("DOMContentLoaded", () => this.initializeApp());
        } else {
            this.initializeApp();
        }
    }

    initializeApp() {
        this.setupEventListeners();
        this.calculateAll();
        this.toggleReturnJourneySection();
        this.initializeCoPassenger();
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
            this.addEventListener('reviewButton', 'click', () => this.handleSubmit());

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
            whatsappField.disabled = true;
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
            // Remove existing listeners to avoid duplicates
            input.removeEventListener('input', this.handleTicketQuantityChange);
            
            // Add new listener with bound context
            input.addEventListener('input', this.handleTicketQuantityChange.bind(this));
        });
    }

    handleTicketQuantityChange() {
        this.calculateTotalTickets();
        this.calculateTicketFee();
        this.updateCoPassengerRows();
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
        return total;
    }

    calculateTotalDepartureTickets() {
        let totalDeparture = 0;
        document.querySelectorAll('.ticket-quantity[data-type="departure"]').forEach(input => {
            totalDeparture += parseInt(input.value) || 0;
        });
        return totalDeparture;
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

    // NEW: Calculate 2% fee for Bkash/Nagad
    calculateDigitalPaymentFee() {
        const ticketFee = parseFloat(this.getValue("ticket_fee")) || 0;
        let totalDigitalPaymentAmount = 0;
        
        // Calculate total amount from Bkash/Nagad payments
        document.querySelectorAll('.payment-entry').forEach(entry => {
            const method = entry.querySelector('.payment-method-select').value;
            const amount = parseFloat(entry.querySelector('.payment-amount-input').value) || 0;
            
            if ((method === 'Bkash' || method === 'Nagad') && amount > 0) {
                totalDigitalPaymentAmount += amount;
            }
        });
        
        // If there are digital payments, calculate 2% fee
        if (totalDigitalPaymentAmount > 0) {
            const digitalPaymentFee = totalDigitalPaymentAmount * 0.02; // 2% fee
            const otherFeeField = document.getElementById('other_fee');
            const currentOtherFee = parseFloat(otherFeeField.value) || 0;
            
            // Set the calculated fee (you might want to add it to existing other fees)
            otherFeeField.value = (currentOtherFee + digitalPaymentFee).toFixed(2);
            
            // Show info about the fee
            this.showDigitalPaymentFeeInfo(digitalPaymentFee, totalDigitalPaymentAmount);
        } else {
            // If no digital payments, ensure the field doesn't show the 2% fee
            this.clearDigitalPaymentFee();
        }
    }
    
    showDigitalPaymentFeeInfo(feeAmount, paymentAmount) {
        const infoDiv = document.getElementById('digitalPaymentFeeInfo');
        if (!infoDiv) {
            const otherFeeDiv = document.getElementById('other_fee').parentNode;
            const newInfoDiv = document.createElement('div');
            newInfoDiv.id = 'digitalPaymentFeeInfo';
            newInfoDiv.className = 'mt-2 text-sm text-blue-600 dark:text-blue-400';
            newInfoDiv.innerHTML = `<i class="fas fa-info-circle mr-1"></i> Includes 2% fee (৳${feeAmount.toFixed(2)}) for Bkash/Nagad payments (2% of ৳${paymentAmount.toFixed(2)})`;
            otherFeeDiv.appendChild(newInfoDiv);
        } else {
            infoDiv.innerHTML = `<i class="fas fa-info-circle mr-1"></i> Includes 2% fee (৳${feeAmount.toFixed(2)}) for Bkash/Nagad payments (2% of ৳${paymentAmount.toFixed(2)})`;
        }
    }
    
    clearDigitalPaymentFee() {
        const infoDiv = document.getElementById('digitalPaymentFeeInfo');
        if (infoDiv) {
            infoDiv.remove();
        }
    }

    // Form Submission
    async handleSubmit() {
        this.clearAllErrors();

        const validationSteps = [
            () => this.validateRequiredFields(),
            () => this.validateMobileNumbers(),
            () => this.validateAmounts(),
            () => this.validatePaymentMethods(),
            () => this.validateCoPassengers()
        ];

        for (let step of validationSteps) {
            const result = step();
            if (!result.isValid) {
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
            if (isDuplicate.exists) {
                this.showDuplicateWarning();
            } else {
                this.submitForm();
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
                this.submitForm();
            }
        });
    }

    submitForm() {
        const form = document.getElementById("ticketForm");
        if (!form) {
            this.showNotification('Form not found', 'error');
            return;
        }

        const reviewButton = document.getElementById("reviewButton");
        const originalText = reviewButton.innerHTML;
        reviewButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Submitting...';
        reviewButton.disabled = true;

        form.submit();
    }

    validateRequiredFields() {
        const requiredFields = [
            { name: "customer_name", label: "Customer Name" },
            { name: "customer_mobile", label: "Mobile Number" },
            { name: "ship_id", label: "Ship Name" },
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

    validateCoPassengers() {
        const wrapper = document.getElementById("coPassengersWrapper");
        const coPassengerRows = wrapper.querySelectorAll('.co-passenger');
        let isValid = true;
        let firstErrorField = null;

        coPassengerRows.forEach((row, index) => {
            const nameField = row.querySelector('input[name$="[name]"]');
            const nameValue = nameField?.value?.toString().trim() || '';

            if (!nameValue) {
                this.showFieldError(nameField, `Co-passenger name is required`);
                isValid = false;
                if (!firstErrorField) firstErrorField = nameField;
            }
        });

        return { isValid, firstErrorField };
    }

    // Co-Passenger Management
    initializeCoPassenger() {
        const wrapper = document.getElementById("coPassengersWrapper");
        const addBtn = document.getElementById("addCoPassengerBtn");

        // Use event delegation for dynamically added remove buttons
        wrapper.addEventListener("click", (e) => {
            if (e.target.closest(".removeCoPassengerBtn")) {
                const coPassengerRow = e.target.closest(".co-passenger");
                if (coPassengerRow) {
                    coPassengerRow.remove();
                    this.updateCoPassengerIndex();
                }
            }
        });

        addBtn.addEventListener("click", () => this.addCoPassengerField(wrapper));

        // Add initial co-passenger row
        this.addCoPassengerField(wrapper);
    }

    updateCoPassengerRows() {
        const totalDepartureTickets = this.calculateTotalDepartureTickets();
        const requiredRows = Math.max(1, totalDepartureTickets); // Always keep at least 1 row
        
        const wrapper = document.getElementById("coPassengersWrapper");
        const currentRows = wrapper.querySelectorAll('.co-passenger').length;
        
        if (requiredRows > currentRows) {
            const rowsToAdd = requiredRows - currentRows;
            for (let i = 0; i < rowsToAdd; i++) {
                this.addCoPassengerField(wrapper);
            }
        } else if (requiredRows < currentRows) {
            const rowsToRemove = currentRows - requiredRows;
            const rows = wrapper.querySelectorAll('.co-passenger');
            
            // Remove rows from the end, but always keep at least 1 row
            for (let i = 0; i < rowsToRemove && rows.length > 1; i++) {
                rows[rows.length - 1 - i].remove();
            }
        }
        
        this.updateCoPassengerIndex();
        this.updateCoPassengerInfoMessage(totalDepartureTickets);
    }

    updateCoPassengerInfoMessage(totalDepartureTickets) {
        const infoDiv = document.getElementById('coPassengerInfo');
        if (!infoDiv) return;
        
        if (totalDepartureTickets > 0) {
            infoDiv.innerHTML = `
                <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200">
                    <p class="text-sm font-medium">
                        <i class="fas fa-info-circle mr-2"></i>
                        ${totalDepartureTickets} departure ticket(s) require ${totalDepartureTickets} co-passenger row(s). All co-passenger names are required.
                    </p>
                </div>
            `;
            infoDiv.classList.remove('hidden');
        } else {
            infoDiv.innerHTML = `
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-200">
                    <p class="text-sm font-medium">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        No departure tickets selected. At least 1 co-passenger row is required.
                    </p>
                </div>
            `;
            infoDiv.classList.remove('hidden');
        }
    }

    updateCoPassengerIndex() {
        const wrapper = document.getElementById("coPassengersWrapper");
        const rows = wrapper.querySelectorAll('.co-passenger');
        
        rows.forEach((row, index) => {
            const nameInput = row.querySelector('input[name$="[name]"]');
            if (nameInput) nameInput.name = `co_passengers[${index}][name]`;
            
            const nidInput = row.querySelector('input[name$="[nid]"]');
            if (nidInput) nidInput.name = `co_passengers[${index}][nid]`;
            
            const mobileInput = row.querySelector('input[name$="[co_passernger_number]"]');
            if (mobileInput) mobileInput.name = `co_passengers[${index}][co_passernger_number]`;
            
            // Update the remove button if it exists
            const removeBtn = row.querySelector('.removeCoPassengerBtn');
            if (removeBtn && rows.length === 1) {
                removeBtn.disabled = true;
                removeBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else if (removeBtn) {
                removeBtn.disabled = false;
                removeBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });
        
        this.coPassengerIndex = rows.length;
    }

    addCoPassengerField(wrapper) {
        const div = document.createElement("div");
        div.classList.add("co-passenger", "grid", "lg:grid-cols-3",  "gap-4", "p-4", "border", "border-gray-200", "dark:border-gray-700", "rounded-lg", "bg-white", "dark:bg-gray-800", "animate-fadeIn");

        div.innerHTML = `
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Co-Passenger Name ${this.coPassengerIndex+1} <span class="text-red-500">*</span>
                </label>
                <input type="text" name="co_passengers[${this.coPassengerIndex}][name]" placeholder="Enter co-passenger name" 
                    class="co-passenger-name w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition"
                    data-required="true">
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
                <button type="button" class="removeCoPassengerBtn px-3 py-2 text-red-600 hover:text-red-800 font-semibold transition bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 rounded-lg">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;

        // Insert before the add button
        const addBtn = document.getElementById("addCoPassengerBtn");
        wrapper.insertBefore(div, addBtn);
        this.coPassengerIndex++;
        
        // Disable remove button if this is the first/only row
        const rows = wrapper.querySelectorAll('.co-passenger');
        if (rows.length === 1) {
            const removeBtn = div.querySelector('.removeCoPassengerBtn');
            if (removeBtn) {
                removeBtn.disabled = true;
                removeBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    // Payment Method Management
    initializePaymentMethod() {
        const wrapper = document.getElementById("paymentInfoWrapper");
        const addBtn = document.getElementById("addPaymentInfo");

        // Use event delegation for dynamically added remove buttons
        wrapper.addEventListener("click", (e) => {
            if (e.target.closest(".removePaymentBtn")) {
                const paymentEntry = e.target.closest(".payment-entry");
                if (paymentEntry) {
                    paymentEntry.remove();
                    this.calculatePaymentTotals();
                    this.calculateTotalPayable();
                    this.calculateDigitalPaymentFee();
                }
            }
        });

        addBtn.addEventListener("click", () => this.addPaymentEntry(wrapper));
        this.addPaymentEntry(wrapper);
    }

    addPaymentEntry(wrapper) {
        const div = document.createElement("div");
        div.classList.add("payment-entry", "md:grid", "grid-cols-7", "gap-4", "p-4", "border", "border-gray-200", "dark:border-gray-700", "rounded-lg", "bg-white", "dark:bg-gray-800");

        div.innerHTML = `
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                   Paid Via <span class="text-red-500">*</span>
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
                   Bikas/Nogod/Bank acc. Number <span class="text-red-500">*</span>
                </label>
                <input type="number" name="payment_methods[${this.paymentIndex}][bank_acc]" 
                    placeholder="Bikas/Nogod/ Bank acc. Number"
                    class="payment-bank_acc-input w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition"
                    step="0.01" min="0" >
            </div>

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                   Paid Amount (৳) <span class="text-red-500">*</span>
                </label>
                <input type="number" name="payment_methods[${this.paymentIndex}][amount]" 
                    placeholder="Enter amount"
                    class="payment-amount-input w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition"
                    step="0.01" min="0" value="0">
            </div>
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                 Payment Date <span class="text-red-500">*</span>
                </label>
                <input type="date"
                    name="payment_methods[${this.paymentIndex}][paid_date]"
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition"
                    value="${new Date().toISOString().slice(0, 10)}">
            </div>

            <div class="flex items-end col-span-1">
                <button type="button" class="removePaymentBtn fa-solid fa-trash w-full px-3 py-2 text-red-600 hover:text-red-800 font-semibold transition hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 rounded-lg">
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

        amountInput.addEventListener('input', () => {
            this.calculatePaymentTotals();
            this.calculateTotalPayable();
            this.calculateDigitalPaymentFee();
        });

        methodSelect.addEventListener('change', () => {
            this.calculateTotalPayable();
            this.calculateDigitalPaymentFee();
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

    // Error Handling
    showFieldError(field, message) {
        this.clearFieldError(field);

        field.classList.add("border-red-500", "dark:border-red-500", "focus:ring-red-500", "focus:border-red-500");
        field.classList.remove("border-gray-300", "dark:border-gray-600", "focus:ring-blue-500", "focus:border-blue-500");

        const errorDiv = document.createElement("div");
        errorDiv.className = "text-red-600 dark:text-red-400 text-sm mt-1 flex items-start animate-fadeIn";
        errorDiv.innerHTML = `
            <svg class="w-4 h-4 mr-1 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            ${message}
        `;

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

        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }

    showNotification(message, type = 'info') {
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