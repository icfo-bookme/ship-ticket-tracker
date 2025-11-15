document.addEventListener("DOMContentLoaded", () => {
    const loader = document.getElementById("loader");
    const table = document.getElementById("salesTable");
    const salesBody = document.getElementById("salesBody");
    const shipFilter = document.getElementById("shipFilter");
    const companyFilter = document.getElementById("companyFilter");
    const journeyDateFilter = document.getElementById("journeyDateFilter");
    const clearFiltersBtn = document.getElementById("clearFilters");

    let dataTableInitialized = false;
    let dataTable;
    let ships = [];
    let companies = [];

    const modal = {
        element: document.getElementById("editModal"),

        show: function () {
            this.element.classList.remove("hidden");
            this.element.classList.add("flex");
        },

        hide: function () {
            this.element.classList.add("hidden");
            this.element.classList.remove("flex");
        },
    };

    function initializeTabs() {
        const tabs = document.querySelectorAll(".status-tab");
        const statusElement = document.getElementById("statusFilter");
        const title = document.querySelector("h2"); // updates the heading dynamically

        tabs.forEach((tab) => {
            tab.addEventListener("click", () => {
                tabs.forEach((t) => {
                    t.classList.remove("bg-blue-950", "text-white");
                    t.classList.add("bg-gray-200", "text-gray-900");
                });

                tab.classList.remove("bg-gray-200", "text-gray-900");
                tab.classList.add("bg-blue-950", "text-white");

                const newStatus = tab.dataset.status;
                statusElement.dataset.status = newStatus;

                title.textContent = `Ship Ticket Sales (${newStatus})`;

                getList();
            });
        });
    }

    function initializeModalEvents() {
        // Close modal when clicking the X button
        document
            .getElementById("closeModalX")
            .addEventListener("click", () => modal.hide());

        // Close modal when clicking the cancel button
        document
            .getElementById("cancelBtn")
            .addEventListener("click", () => modal.hide());

        // Close modal when clicking outside the modal content
        modal.element.addEventListener("click", (e) => {
            if (e.target === modal.element) {
                modal.hide();
            }
        });
    }

    async function fetchShips() {
        try {
            const response = await fetch("/ships");
            ships = await response.json();
            populateDropdown(shipFilter, ships, "All Ships");
            populateEditShipDropdown();
        } catch (error) {
            console.error("Error fetching ships:", error);
        }
    }

    async function fetchCompanies() {
        try {
            const response = await fetch("/companies");
            companies = await response.json();
            populateDropdown(companyFilter, companies, "All Companies");
            populateEditCompanyDropdown();
        } catch (error) {
            console.error("Error fetching companies:", error);
        }
    }

    function populateDropdown(selectElement, data, defaultText) {
        if (!selectElement) return;

        selectElement.innerHTML = "";
        const defaultOption = document.createElement("option");
        defaultOption.value = "";
        defaultOption.textContent = defaultText;
        defaultOption.selected = true;
        selectElement.appendChild(defaultOption);

        data.forEach((item) => {
            const option = document.createElement("option");
            option.value = item.id;
            option.textContent = item.name;
            selectElement.appendChild(option);
        });
    }

    function populateEditShipDropdown() {
        const editShipSelect = document.getElementById("editShip");
        if (!editShipSelect) return;

        editShipSelect.innerHTML = '<option value="">Select Ship</option>';
        ships.forEach((ship) => {
            const option = document.createElement("option");
            option.value = ship.id;
            option.textContent = ship.name;
            editShipSelect.appendChild(option);
        });
    }

    function populateEditCompanyDropdown() {
        const editCompanySelect = document.getElementById("editCompany");
        if (!editCompanySelect) return;

        editCompanySelect.innerHTML =
            '<option value="">Select Company</option>';
        companies.forEach((company) => {
            const option = document.createElement("option");
            option.value = company.id;
            option.textContent = company.name;
            editCompanySelect.appendChild(option);
        });
    }

    // Filter event listeners
    shipFilter.addEventListener("change", getList);
    companyFilter.addEventListener("change", getList);
    journeyDateFilter.addEventListener("change", getList);

    // Clear filters
    clearFiltersBtn.addEventListener("click", () => {
        shipFilter.value = "";
        companyFilter.value = "";
        journeyDateFilter.value = "";
        getList();
    });

    async function getList() {
        try {
            loader.style.display = "block";

            const selectedShipId = shipFilter.value;
            const selectedCompanyId = companyFilter.value;
            const selectedJourneyDate = journeyDateFilter.value;
            const statusElement = document.getElementById("statusFilter");
            const status = statusElement ? statusElement.dataset.status : "pending";

            // Destroy existing DataTable if initialized
            if (dataTableInitialized && dataTable) {
                dataTable.destroy();
                dataTableInitialized = false;
            }

            // Clear table body
            salesBody.innerHTML = "";

            loader.style.display = "none";
            table.classList.remove("hidden");

            // Initialize DataTable with server-side processing
            dataTable = $("#salesTable").DataTable({
                processing: true,
                serverSide: true, // KEY CHANGE: Enable server-side processing
                "ordering": false,
                ajax: {
                    url: `/sales/${status}`,
                    type: 'GET',
                    data: function (d) {
                        // Add custom filters
                        d.ship_id = selectedShipId;
                        d.company_id = selectedCompanyId;
                        d.journey_date = selectedJourneyDate;
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'customer_name' },
                    { data: 'customer_mobile' },
                    {
                        data: null,
                        render: function (data) {
                            return data.ship?.name || data.ships?.name || 'Not available';
                        }
                    },
                    {
                        data: 'journey_date',
                        render: function (data) {
                            return new Date(data).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            });
                        }
                    },
                    { data: 'ticket_fee' },
                    {
                        data: 'companies.name', // Change this from 'companies.name'
                        render: function (data, type, row) {
                            return data || row.company?.name || 'Not available';
                        }
                    },
                    { data: 'status' },
                    {
                        data: null,
                        orderable: false,
                        render: function (data, type, row) {
                            return createActionButtons(row);
                        }
                    }
                ],
                dom: "lBfrtip",
                lengthChange: true,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                buttons: ['copy', 'excel', 'csv', 'pdf', 'print', 'colvis'],
                drawCallback: function () {
                    attachEventListeners();
                }
            });

            dataTableInitialized = true;

        } catch (error) {
            console.error("Error initializing DataTable:", error);
            loader.textContent = "Failed to load data. Please try again later.";
        }
    }

    function createActionButtons(sale) {
        const verifyByName = sale.verifyby?.length > 0
            ? sale.verifyby[0].verified_by_user?.name
            : 'Unknown';

        return `
        <div class="flex gap-2 items-center justify-center">
            <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" 
             data-id="${sale.id}" 
            data-customer="${sale.customer_name}" 
            data-mobile="${sale.customer_mobile}" 
            data-email="${sale.email}" 
            data-nid="${sale.nid}" 
            data-source="${sale.sales_source}"
            data-ship="${sale.ship_id}"
            data-journeyDate="${sale.journey_date}"
            data-returnDate="${sale.return_date}"
            data-ticketFee="${sale.ticket_fee}"
            data-payment_method="${sale.payment_method}"
            data-number_of_ticket="${sale.number_of_ticket}"
            data-receivedAmount="${sale.received_amount}"
            data-dueAmount="${sale.due_amount}"
            data-companyId="${sale.company_id}"
            data-issuedDate="${sale.issued_date}"
            data-ticket_category="${sale.ticket_category}"
            data-soldBy="${sale.sold_by || ""}"
            data-status="${sale.status}"
                data-sale='${JSON.stringify(sale)}'>
            </button>
            <button class="fas fa-trash text-red-500 px-2 py-1 border border-gray-300 rounded deleteBtn" 
                data-id="${sale.id}">
            </button>
            ${createStatusButton(sale, verifyByName)}
        </div>
    `;
    }

    function createStatusButton(sale, verifyByName) {
        const statusButtons = {
            'pending': `<button class="bg-red-500 text-white px-2 py-1 rounded verifyBtn" 
            data-id="${sale.id}" data-status="payment-verified" 
            title="Sold by: ${sale.sold_by}">Verify Payment</button>`,

            'payment-verified': `<button class="bg-green-500 text-white px-2 py-1 rounded verifyBtn" 
            data-id="${sale.id}" data-status="ticket-issued" 
            title="payment-verified by: ${verifyByName}">Ticket Issued</button>`,

            'ticket-issued': `<button class="bg-blue-500 text-white px-2 py-1 rounded verifyBtn" 
            data-id="${sale.id}" data-status="ticket-printed" 
            title="ticket-issued by: ${verifyByName}">Ticket Printed</button>`,

            'ticket-printed': `<button class="bg-blue-500 text-white px-2 py-1 rounded shipmentIdEntryBtn" 
            data-id="${sale.id}" data-status="shipment_id_entered" 
            title="ticket-printed by: ${verifyByName}">Entry Shipment ID</button>`,

            'shipment_id_entered': `<button class="bg-blue-500 text-white px-2 py-1 rounded verifyBtn" 
            data-id="${sale.id}" data-status="shipped" 
            title="shipment_id_entered by: ${verifyByName}">Shipped</button>`
        };

        return statusButtons[sale.status] || '';
    }

    function attachEventListeners() {
        document.querySelectorAll(".editBtn").forEach((btn) => {
            btn.addEventListener("click", () => showEditModal(btn));
        });

        document.querySelectorAll(".deleteBtn").forEach((btn) => {
            btn.addEventListener("click", () => deleteSale(btn, getList));
        });

        document.querySelectorAll(".verifyBtn").forEach((btn) => {
            btn.addEventListener("click", () => varifySale(btn, getList));
        });

        document.querySelectorAll(".shipmentIdEntryBtn").forEach((btn) => {
            btn.addEventListener("click", () => varifyShipment(btn, getList));
        });
    }

    function showEditModal(btn) {
        populateEditShipDropdown();
        populateEditCompanyDropdown();

        const fields = {
            editId: btn.dataset.id,
            editCustomerName: btn.dataset.customer,
            editnid: btn.dataset.nid,
            editMobile: btn.dataset.mobile,
            editEmail: btn.dataset.email,
            editSalesSource: btn.dataset.source,
            editShip: btn.dataset.ship,
            editJourneyDate: formatDateForInput(btn.dataset.journeydate),
            editReturnDate: formatDateForInput(btn.dataset.returndate),
            editTicketFee: btn.dataset.ticketfee,
            editTicketNumber: btn.dataset.number_of_ticket,
            editPaymentMethod: btn.dataset.payment_method,
            editReceivedAmount: btn.dataset.receivedamount,
            editDueAmount: btn.dataset.dueamount,
            editCompany: btn.dataset.companyid,
            editIssuedDate: formatDateForInput(btn.dataset.issueddate),
            editSoldBy: btn.dataset.soldby,
            editStatus: btn.dataset.status,
            editTicketCategory: btn.dataset.ticket_category,
        };

        // Set values for all fields
        Object.keys(fields).forEach((fieldId) => {
            const element = document.getElementById(fieldId);
            if (element) {
                element.value = fields[fieldId] || "";
            }
        });

        // Show the edit modal
        modal.show();
    }

    function formatDateForInput(dateString) {
        if (!dateString || dateString === "Not specified") return "";

        const date = new Date(dateString);
        if (isNaN(date.getTime())) return "";

        return date.toISOString().split("T")[0];
    }

    document
        .getElementById("editForm")
        .addEventListener("submit", async (e) => {
            e.preventDefault();

            const id = document.getElementById("editId").value;
            const data = {
                customer_name:
                    document.getElementById("editCustomerName").value,
                customer_mobile: document.getElementById("editMobile").value,
                nid: document.getElementById("editnid").value,
                email: document.getElementById("editEmail").value,
                sales_source: document.getElementById("editSalesSource").value,
                ship_id: document.getElementById("editShip").value,
                journey_date: document.getElementById("editJourneyDate").value,
                return_date: document.getElementById("editReturnDate").value,
                ticket_fee: document.getElementById("editTicketFee").value,
                number_of_ticket:
                    document.getElementById("editTicketNumber").value,
                payment_method:
                    document.getElementById("editPaymentMethod").value,
                received_amount:
                    document.getElementById("editReceivedAmount").value,
                due_amount: document.getElementById("editDueAmount").value,
                company_id: document.getElementById("editCompany").value,
                issued_date: document.getElementById("editIssuedDate").value,
                sold_by: document.getElementById("editSoldBy").value,
                status: document.getElementById("editStatus").value,
                ticket_category:
                    document.getElementById("editTicketCategory").value,
            };

            await updateSale(id, data);
        });

    // Function to update sale
    async function updateSale(id, data) {
        try {
            const response = await fetch(`/sales/status/${id}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute("content"),
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (response.ok) {
                Swal.fire({
                    title: "Success!",
                    text: "Sale updated successfully!",
                    icon: "success",
                    confirmButtonText: "OK",
                    customClass: {
                        confirmButton: "bg-blue-950 text-white",
                    },
                });

                getList();
                modal.hide();
            } else {
                throw new Error(result.message || "Failed to update sale");
            }
        } catch (error) {
            console.error("Error updating sale:", error);
            Swal.fire({
                title: "Error!",
                text: "Failed to update sale. Please try again.",
                icon: "error",
                confirmButtonText: "OK",
                customClass: {
                    confirmButton: "bg-red-600 text-white",
                },
            });
        }
    }

    // Initialize the page
    async function initializePage() {
        initializeModalEvents();
        await fetchShips();
        initializeTabs();
        await fetchCompanies();
        getList();
    }

    initializePage();
});