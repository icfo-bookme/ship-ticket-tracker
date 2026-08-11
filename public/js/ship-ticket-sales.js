class TicketSalesSystem {
    constructor() {
        this.currentPackages = [];
        this.paymentIndex = 0;
        this.coPassengerIndex = 0;
        this.selectors = this.initializeSelectors();
        this.constants = this.initializeConstants();
        this.init();
    }

    //  INITIALIZATION 
    init() {
        document.addEventListener("DOMContentLoaded", () => {
            this.setupEventListeners();
            this.calculateAll();
            this.toggleReturnJourneySection();
        });
    }

    initializeSelectors() {
        return {
            elements: {
                reviewButton: '#reviewButton',
                shipSelect: '#ship_id',
                returnDate: '#return_date',
                mobileField: '[name="customer_mobile"]',
                whatsappField: '[name="whatsapp"]',
                sameAsMobileCheckbox: '#sameAsMobileCheckbox',
                ticketFee: '#ticket_fee',
                receivedAmount: '#received_amount',
                otherFee: '#other_fee',
                totalTickets: '#total_tickets',
                totalPayable: '#total_payable',
                dueAmount: '#due_amount',
                departureContainer: '#departureTicketCategoriesContainer',
                returnContainer: '#returnTicketCategoriesContainer',
                noDepartureMessage: '#noDepartureCategoriesMessage',
                noReturnMessage: '#noReturnCategoriesMessage',
                coPassengersWrapper: '#coPassengersWrapper',
                addCoPassengerBtn: '#addCoPassengerBtn',
                coPassengerInfo: '#coPassengerInfo',
                paymentInfoWrapper: '#paymentInfoWrapper',
                addPaymentInfo: '#addPaymentInfo',
                reviewModal: '#reviewModal',
                reviewContent: '#reviewContent',
                bftnSelect: 'select[name="bftn_status"]',
                bftnIssueDateWrapper: '#bftnIssueDateWrapper'
            },
            classes: {
                ticketQuantity: '.ticket-quantity',
                paymentEntry: '.payment-entry',
                paymentMethodSelect: '.payment-method-select',
                paymentAmountInput: '.payment-amount-input',
                removePaymentBtn: '.removePaymentBtn',
                coPassenger: '.co-passenger',
                removeCoPassengerBtn: '.removeCoPassengerBtn'
            }
        };
    }

    initializeConstants() {
        return {
            MOBILE_REGEX: /^01[2-9]\d{8}$/,
            FIELD_LABELS: {
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
                bftn_issue_datetime: "BFTN Issue Date & Time",
                issued_date: "Issued Date",
                sold_by: "Sold By",
                number_of_ticket: "Total Tickets",
                remark1: "Remark 1",
                remark2: "Remark 2",
            },
            REQUIRED_FIELDS: [
                { name: "customer_name", label: "Customer Name" },
                { name: "customer_mobile", label: "Mobile Number" },
                { name: "address", label: "Address" },
                { name: "sales_source", label: "Sales Source" },
                { name: "ship_id", label: "Ship Name" },
                { name: "ticket_fee", label: "Total Ticket Value" },
                { name: "company_id", label: "Company Name" },
                { name: "sold_by", label: "Sold By" },
                { name: "whatsapp", label: "WhatsApp Number" }
            ]
        };
    }

    //  EVENT HANDLERS 
    setupEventListeners() {
        try {
            this.setupCalculationListeners();
            this.setupFormValidationListeners();
            this.setupMainActionListeners();
            this.setupShipAndJourneyListeners();
            this.setupMobileAndWhatsAppListeners();

            this.initializeComponents();
        } catch (error) {
            console.error('Error setting up event listeners:', error);
            this.showNotification('Error initializing form. Please refresh the page.', 'error');
        }
    }

    setupCalculationListeners() {
        this.addEventListener('ticket_fee', 'input', () => this.calculateAll());
        this.addEventListener('received_amount', 'input', () => this.calculateDue());
        this.addEventListener('other_fee', 'input', () => this.calculateAll());
    }

    setupFormValidationListeners() {
        document.querySelectorAll("input, select").forEach((field) => {
            field.addEventListener("input", () => this.clearFieldError(field));
        });
    }

    setupMainActionListeners() {
        this.addEventListener('reviewButton', 'click', () => this.handleReviewClick());
        this.setupFormSubmitGuard();
    }

    setupFormSubmitGuard() {
        const form = this.getElement('#ticketForm');
        if (!form) return;

        form.addEventListener('submit', () => {
            const submitBtn = document.querySelector('button[form="ticketForm"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        });
    }

    setupShipAndJourneyListeners() {
        this.addEventListener('ship_id', 'change', () => this.loadTicketCategories());
        this.addEventListener('return_date', 'change', () => this.toggleReturnJourneySection());
    }

    setupMobileAndWhatsAppListeners() {
        const mobileField = this.getElement(this.selectors.elements.mobileField);
        mobileField.addEventListener('input', () => {
            const checkbox = this.getElement(this.selectors.elements.sameAsMobileCheckbox);
            if (checkbox.checked) this.handleSameAsMobileCheckbox();
        });

        this.addEventListener('sameAsMobileCheckbox', 'change', () => this.handleSameAsMobileCheckbox());
    }

    initializeComponents() {
        this.initializeCoPassenger();
        this.initializePaymentMethod();
        this.initBftnIssueDateToggle();
    }

    //  DOM UTILITIES 
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

    getElements(selector) {
        return document.querySelectorAll(selector);
    }

    getValue(elementId) {
        const element = document.getElementById(elementId);
        return element ? element.value.trim() : '';
    }

    setValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) element.value = value;
    }

    //  MOBILE & WHATSAPP 
    handleSameAsMobileCheckbox() {
        const checkbox = this.getElement(this.selectors.elements.sameAsMobileCheckbox);
        const mobileField = this.getElement(this.selectors.elements.mobileField);
        const whatsappField = this.getElement(this.selectors.elements.whatsappField);

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

            whatsappField.value = mobileValue;
            whatsappField.classList.add('bg-gray-100', 'dark:bg-gray-800', 'text-gray-500', 'dark:text-gray-400');
            this.clearFieldError(whatsappField);
        } else {
            whatsappField.disabled = false;
            whatsappField.classList.remove('bg-gray-100', 'dark:bg-gray-800', 'text-gray-500', 'dark:text-gray-400');
            whatsappField.value = '';
        }
    }

    // TICKET CATEGORIES 
    async loadTicketCategories() {
        const shipId = this.getValue("ship_id");
        const returnDate = this.getValue("return_date");
        const departureContainer = this.getElement(this.selectors.elements.departureContainer);
        const returnContainer = this.getElement(this.selectors.elements.returnContainer);
        const noDepartureMessage = this.getElement(this.selectors.elements.noDepartureMessage);
        const noReturnMessage = this.getElement(this.selectors.elements.noReturnMessage);

        if (!shipId) {
            departureContainer.innerHTML = '';
            returnContainer.innerHTML = '';
            noDepartureMessage.classList.remove("hidden");
            noReturnMessage.classList.remove("hidden");
            return;
        }

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
        const departureContainer = this.getElement(this.selectors.elements.departureContainer);
        const returnContainer = this.getElement(this.selectors.elements.returnContainer);
        const noDepartureMessage = this.getElement(this.selectors.elements.noDepartureMessage);
        const noReturnMessage = this.getElement(this.selectors.elements.noReturnMessage);

        departureContainer.innerHTML = '';
        returnContainer.innerHTML = '';

        if (packages?.length > 0) {
            packages.forEach((pkg, index) => {
                departureContainer.appendChild(this.createCategoryField(pkg, index, 'departure'));
            });

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
            this.updateCoPassengerRows();
        } else {
            this.showNoCategoriesMessage(departureContainer, returnContainer);
        }
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
                    ${this.escapeHtml(pkg.name || 'Unnamed Category')} (${type === 'departure' ? 'Departure' : 'Return'})
                </label>
                <input type="hidden" name="ticket_categories[${type}][${index}][name]" value="${this.escapeHtml(pkg.name || '')}">
                <input type="hidden" name="ticket_categories[${type}][${index}][package_id]" value="${pkg.id || ''}">
                <input type="hidden" class="ticket-price" data-package-id="${pkg.id}" data-type="${type}" value="${singlePrice}">
                <input type="hidden" class="ticket-round-trip-price" data-package-id="${pkg.id}" data-type="${type}" value="${roundTripPrice}">
                <input type="hidden" class="ticket-return-price" data-package-id="${pkg.id}" data-type="${type}" value="${returnTripPrice}">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Category: ${this.escapeHtml(pkg.name || 'Unnamed')}<br>
                    ${type === 'departure' ? `Departure: ৳${singlePrice.toFixed(2)}` : `Return: ৳${returnTripPrice.toFixed(2)}`}
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
        // Elements are freshly rebuilt on every render, so attaching directly is
        // safe and avoids the previous dead removeEventListener call.
        this.getElements(this.selectors.classes.ticketQuantity).forEach(input => {
            input.addEventListener('input', () => this.handleTicketQuantityChange());
        });
    }

    handleTicketQuantityChange() {
        this.calculateTotalTickets();
        this.calculateTicketFee();
        this.updateCoPassengerRows();
    }

    //  CALCULATION METHODS 
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
        this.getElements(this.selectors.classes.ticketQuantity).forEach(input => {
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
        let total = 0;

        this.currentPackages.forEach(pkg => {
            const pkgId = pkg.id;
            const departureQty = this.getQuantity(pkgId, 'departure');
            const returnQty = this.getQuantity(pkgId, 'return');
            const singlePrice = parseFloat(pkg.price) || 0;
            const roundTripPrice = parseFloat(pkg.round_trip_price) || 0;
            const returnOnlyPrice = roundTripPrice > 0 ? roundTripPrice - singlePrice : singlePrice;

            const paired = Math.min(departureQty, returnQty);

            if (paired > 0 && roundTripPrice > 0) {
                total += paired * roundTripPrice;
            } else {
                total += paired * (singlePrice + returnOnlyPrice);
            }

            const remainingDeparture = departureQty - paired;
            const remainingReturn = returnQty - paired;

            total += remainingDeparture * singlePrice;
            total += remainingReturn * returnOnlyPrice;
        });

        return total;
    }

    getQuantity(packageId, type) {
        const input = this.getElement(
            `.ticket-quantity[data-package-id="${packageId}"][data-type="${type}"]`
        );
        return parseInt(input?.value) || 0;
    }

    calculateTotalTickets() {
        let total = 0;
        this.getElements(this.selectors.classes.ticketQuantity).forEach(input => {
            total += parseInt(input.value) || 0;
        });
        this.setValue("total_tickets", total);
    }

    calculateTotalDepartureTickets() {
        let totalDeparture = 0;
        this.getElements('.ticket-quantity[data-type="departure"]').forEach(input => {
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

    //  FORM VALIDATION 
    async handleReviewClick() {
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
                this.showReviewModal();
            }
        } catch (error) {
            console.error('Error checking duplicate:', error);
            this.showNotification('Something went wrong while checking duplicates.', 'error');
        }
    }

    validateRequiredFields() {
        let isValid = true;
        let firstErrorField = null;

        this.constants.REQUIRED_FIELDS.forEach(({ name, label }) => {
            const field = this.getElement(`[name="${name}"]`);
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

        const mobileField = this.getElement(this.selectors.elements.mobileField);
        const mobileValue = mobileField.value.trim();

        if (mobileValue && !this.isValidMobile(mobileValue)) {
            this.showFieldError(mobileField, "Enter valid mobile number (01XXXXXXXXX)");
            isValid = false;
            firstErrorField = mobileField;
        }

        const whatsappField = this.getElement(this.selectors.elements.whatsappField);
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
            this.showFieldError(this.getElement(this.selectors.elements.ticketFee), "Ticket fee must be greater than 0");
            isValid = false;
            firstErrorField = this.getElement(this.selectors.elements.ticketFee);
        }

        if (receivedAmount <= 0) {
            this.showFieldError(this.getElement(this.selectors.elements.receivedAmount), "Received amount must be greater than 0");
            isValid = false;
            if (!firstErrorField) firstErrorField = this.getElement(this.selectors.elements.receivedAmount);
        }

        return { isValid, firstErrorField };
    }

    validatePaymentMethods() {
        const paymentEntries = this.getElements(this.selectors.classes.paymentEntry);
        let hasValidPayment = false;

        paymentEntries.forEach(entry => {
            const method = entry.querySelector(this.selectors.classes.paymentMethodSelect).value;
            const amount = parseFloat(entry.querySelector(this.selectors.classes.paymentAmountInput).value) || 0;

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
        const wrapper = this.getElement(this.selectors.elements.coPassengersWrapper);
        const coPassengerRows = wrapper.querySelectorAll(this.selectors.classes.coPassenger);
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

    //  CO-PASSENGER MANAGEMENT 
    initializeCoPassenger() {
        const wrapper = this.getElement(this.selectors.elements.coPassengersWrapper);
        const addBtn = this.getElement(this.selectors.elements.addCoPassengerBtn);

        wrapper.addEventListener("click", (e) => {
            if (e.target.closest(this.selectors.classes.removeCoPassengerBtn)) {
                const coPassengerRow = e.target.closest(this.selectors.classes.coPassenger);
                if (coPassengerRow) {
                    coPassengerRow.remove();
                    this.updateCoPassengerIndex();
                }
            }
        });

        addBtn.addEventListener("click", () => this.addCoPassengerField(wrapper));
        // this.addCoPassengerField(wrapper);
    }

    updateCoPassengerRows() {
        const totalDepartureTickets = this.calculateTotalDepartureTickets();
        const requiredRows = Math.max(1, totalDepartureTickets); // always keep at least 1 row
        const wrapper = this.getElement(this.selectors.elements.coPassengersWrapper);
        const currentRows = wrapper.querySelectorAll(this.selectors.classes.coPassenger).length;

        if (requiredRows > currentRows) {
            const rowsToAdd = requiredRows - currentRows;
            for (let i = 0; i < rowsToAdd; i++) {
                this.addCoPassengerField(wrapper);
            }
        } else if (requiredRows < currentRows) {
            const rowsToRemove = currentRows - requiredRows;
            const rows = wrapper.querySelectorAll(this.selectors.classes.coPassenger);

            for (let i = 0; i < rowsToRemove && rows.length > 1; i++) {
                rows[rows.length - 1 - i].remove();
            }
        }

        this.updateCoPassengerIndex();
        this.updateCoPassengerInfoMessage(totalDepartureTickets);
    }

    updateCoPassengerInfoMessage(totalDepartureTickets) {
        const infoDiv = this.getElement(this.selectors.elements.coPassengerInfo);
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
        const wrapper = this.getElement(this.selectors.elements.coPassengersWrapper);
        const rows = wrapper.querySelectorAll(this.selectors.classes.coPassenger);

        rows.forEach((row, index) => {
            const nameInput = row.querySelector('input[name$="[name]"]');
            if (nameInput) nameInput.name = `co_passengers[${index}][name]`;

            const nidInput = row.querySelector('input[name$="[nid]"]');
            if (nidInput) nidInput.name = `co_passengers[${index}][nid]`;

            const mobileInput = row.querySelector('input[name$="[co_passernger_number]"]');
            if (mobileInput) mobileInput.name = `co_passengers[${index}][co_passernger_number]`;

            const removeBtn = row.querySelector(this.selectors.classes.removeCoPassengerBtn);
            if (removeBtn) {
                removeBtn.disabled = rows.length === 1;
                removeBtn.classList.toggle('opacity-50', rows.length === 1);
                removeBtn.classList.toggle('cursor-not-allowed', rows.length === 1);
            }
        });

        this.coPassengerIndex = rows.length;
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
                    <input type="number" name="co_passengers[${this.coPassengerIndex}][co_passernger_number]" placeholder="Enter Mobile Number"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition">
                </div>
                <button type="button" class="removeCoPassengerBtn fa-solid fa-trash px-3 py-2 text-red-600 hover:text-red-800 font-semibold transition"></button>
            </div>

            

            <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Date Of Birth
                            </label>
                            <input type="date" name="co_passengers[${this.coPassengerIndex}][date_of_birth]" 
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">
                        </div>

             
        `;

        wrapper.insertBefore(div, this.getElement(this.selectors.elements.addCoPassengerBtn));
        this.coPassengerIndex++;
    }

    // PAYMENT MANAGEMENT 
    initializePaymentMethod() {
        const wrapper = this.getElement(this.selectors.elements.paymentInfoWrapper);
        const addBtn = this.getElement(this.selectors.elements.addPaymentInfo);

        addBtn.addEventListener("click", () => this.addPaymentEntry(wrapper));
        this.addPaymentEntry(wrapper);
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

            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                   Transection Id <span class="text-red-500">*</span>
                </label>
                <input type="text" name="payment_methods[${this.paymentIndex}][transaction_id]" 
                    placeholder="Enter transaction ID"
                    class="payment-transaction-id-input w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition"
                   >
            </div>

            <div class="col-span-2">
  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
    Date & Time
  </label>

  <input
  type="datetime-local"
  name="payment_methods[${this.paymentIndex}][payment_datetime]"
  value="${(() => {
                const d = new Date();
                d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
                return d.toISOString().slice(0, 16);
            })()}"
  class="w-full border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 shadow-sm"
>

</div>



            <div class="col-span-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Remarks (optional)
                </label>
                <textarea
                    name="payment_methods[${this.paymentIndex}][remark]"
                    placeholder="Enter remarks"
                    rows="3"
                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition"
                ></textarea>
            </div>

            <div class="flex items-end col-span-1">
                <button type="button"
                    class="removePaymentBtn fa-solid fa-trash w-full px-3 py-2 text-red-600 hover:text-red-800 font-semibold transition hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 rounded-lg">
                </button>
            </div>
        `;

        wrapper.insertBefore(div, this.getElement(this.selectors.elements.addPaymentInfo));
        this.attachPaymentEventListeners(div);
        this.paymentIndex++;
    }

    attachPaymentEventListeners(div) {
        const amountInput = div.querySelector(this.selectors.classes.paymentAmountInput);
        const methodSelect = div.querySelector(this.selectors.classes.paymentMethodSelect);
        const removeBtn = div.querySelector(this.selectors.classes.removePaymentBtn);

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
        this.getElements(this.selectors.classes.paymentAmountInput).forEach(input => {
            totalReceived += parseFloat(input.value) || 0;
        });
        this.setValue("received_amount", totalReceived.toFixed(2));
        this.calculateDue();
    }

    // REVIEW MODAL 
    showReviewModal() {
        this.fillReviewContent();

        const modal = this.getElement(this.selectors.elements.reviewModal);
        modal.classList.remove("hidden");
        modal.classList.add("flex");

        // Attach close handlers only once to avoid stacking listeners on repeated opens.
        if (modal.dataset.initialized === undefined) {
            modal.dataset.initialized = '1';

            modal.addEventListener("click", (e) => {
                if (e.target === modal || e.target.id === "modalBackdrop") this.closeModal();
            });

            this.getElement('#editInfoButton').addEventListener("click", () => this.closeModal());
            this.getElements('[data-modal-hide="reviewModal"]').forEach((btn) => {
                btn.addEventListener("click", () => this.closeModal());
            });
        }
    }

    closeModal() {
        const modal = this.getElement(this.selectors.elements.reviewModal);
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }

    fillReviewContent() {
        const formData = new FormData(this.getElement("#ticketForm"));
        let html = this.generateReviewContent(formData);
        this.getElement(this.selectors.elements.reviewContent).innerHTML = html;
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
        let content = '';
        for (const [field, label] of Object.entries(this.constants.FIELD_LABELS)) {
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

        if (field === "ship_id" || field === "company_id") {
            const select = this.getElement(`select[name="${field}"]`);
            return select?.options[select.selectedIndex]?.text || "Not specified";
        }

        if (["ticket_fee", "total_payable", "received_amount", "due_amount"].includes(field)) {
            return "৳ " + (parseFloat(value) || 0).toFixed(2);
        }

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
        this.getElements(this.selectors.classes.paymentEntry).forEach(entry => {
            const method = entry.querySelector(this.selectors.classes.paymentMethodSelect).value;
            const amount = parseFloat(entry.querySelector(this.selectors.classes.paymentAmountInput).value) || 0;
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
        this.getElements(`.ticket-quantity[data-type="${type}"]`).forEach(input => {
            const quantity = parseInt(input.value) || 0;
            if (quantity > 0) {
                const name = input.closest('div').previousElementSibling.querySelector('input[name$="[name]"]').value;
                categories.push({ name, quantity, type: type === 'departure' ? 'Departure' : 'Return' });
            }
        });
        return categories;
    }

    generateCoPassengerInfo() {
        const coPassengerFields = [...this.getElements(this.selectors.classes.coPassenger)];
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
            const dob = group.querySelector('input[name^="co_passengers"][name$="[date_of_birth]"]')?.value?.trim() || "Not specified";
            html += `
                <div class="border-b flex item-center gap-5 border-gray-100 dark:border-gray-700 pb-2">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">#${index + 1}. ${this.escapeHtml(name)}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">NID: ${this.escapeHtml(nid)}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Number: ${this.escapeHtml(number)}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">DOB: ${this.escapeHtml(dob)}</p>
                </div>`;
        });

        html += `</div></div>`;
        return html;
    }

    //  UTILITY METHODS 
    toggleReturnJourneySection() {
        const returnDate = this.getValue("return_date");
        const returnSection = this.getElement("#returnJourneySection");
        const noReturnMessage = this.getElement("#noReturnCategoriesMessage");

        if (returnDate) {
            returnSection.style.display = 'block';
            noReturnMessage.classList.add("hidden");
            this.loadTicketCategories();
        } else {
            returnSection.style.display = 'none';
            this.getElement("#returnTicketCategoriesContainer").innerHTML = '';
            noReturnMessage.classList.remove("hidden");
            this.calculateTotalTickets();
            this.calculateTicketFee();
            this.updateCoPassengerRows();
        }
    }

    showNoCategoriesMessage(departureContainer, returnContainer) {
        const messageHTML = '<div class="text-gray-500 dark:text-gray-400">No ticket categories available for this ship.</div>';
        departureContainer.innerHTML = messageHTML;
        returnContainer.innerHTML = messageHTML;
    }

    async checkDuplicateTicket() {
        const customerMobile = this.getElement('[name="customer_mobile"]').value.trim();
        const journeyDate = this.getElement('[name="journey_date"]').value;

        const response = await fetch("/ship-ticket-sales/check-duplicate", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": this.getElement('meta[name="csrf-token"]').getAttribute("content"),
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

    isValidMobile(mobile) {
        return this.constants.MOBILE_REGEX.test(mobile);
    }

    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    initBftnIssueDateToggle() {
        const bftnSelect = this.getElement(this.selectors.elements.bftnSelect);
        const issueWrapper = this.getElement(this.selectors.elements.bftnIssueDateWrapper);

        if (!bftnSelect || !issueWrapper) return;

        const toggleBftnIssueDate = () => {
            if (bftnSelect.value === 'yes') {
                issueWrapper.classList.remove('hidden');
            } else {
                issueWrapper.classList.add('hidden');
            }
        };

        toggleBftnIssueDate();
        bftnSelect.addEventListener('change', toggleBftnIssueDate);
    }

    //  ERROR HANDLING 
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
        this.getElements("input, select").forEach(field => this.clearFieldError(field));
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

const ticketSystem = new TicketSalesSystem();