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

    // Reusable modal functions
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
        } catch (error) {
            console.error("Error fetching ships:", error);
        }
    }

    async function fetchCompanies() {
        try {
            const response = await fetch("/companies");
            companies = await response.json();
            populateDropdown(companyFilter, companies, "All Companies");
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

            dataTable = $("#salesTable").DataTable({
                processing: true,
                serverSide: true,
                "ordering": false,
                ajax: {
                    url: `/all/refundable`,
                    type: 'GET',
                    data: function (d) {
                        d.ship_id = selectedShipId;
                        d.company_id = selectedCompanyId;
                        d.journey_date = selectedJourneyDate;
                    }
                },
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return `<input type="checkbox" class="selectSale" data-id="${row.id}" />`;
                        }
                    },
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
                    { data: 'companies.name' },
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
             <td class="border border-gray-300 px-4 py-2 flex gap-5 items-center justify-center">
        <button class="fas fa-eye  text-blue-950 px-2 py-1 rounded showBtn" 
            data-id="${sale.id}" 
            data-customer="${sale.customer_name}" 
            data-mobile="${sale.customer_mobile}" 
            data-email="${sale.email}" 
            data-nid="${sale.nid}" 
            data-source="${sale.sales_source}"
            data-ship="${sale.ship_id}"
            data-ship-name="${sale.ship
                ? sale.ship.name
                : sale.ships
                    ? sale.ships.name
                    : "Not available"
            }"
            data-journeyDate="${sale.journey_date}"
            data-returnDate="${sale.return_date}"
            data-ticketFee="${sale.ticket_fee}"
            data-payment_method="${sale.payment_method}"
            data-number_of_ticket="${sale.number_of_ticket}"
            data-receivedAmount="${sale.received_amount}"
            data-dueAmount="${sale.due_amount}"
            data-companyId="${sale.company_id}"
            data-company-name="${sale.companies.name}"
            data-issuedDate="${sale.issued_date}"
            data-ticket_category="${sale.ticket_category}"
            data-soldBy="${sale.sold_by || ""}"
            data-status="${sale.status}">    
        </button>
            <button class="bg-blue-900 text-white px-2 py-1 rounded verifyRefund" 
                data-id="${sale.id}"
                data-received_total_amount="${sale.received_amount}"
                data-number_ticket="${sale.number_of_ticket}"
                data-status="shipped">
               Partial Refund
            </button>
      
    </td>
          
        </div>
    `;
    }

    function attachEventListeners() {
        document.querySelectorAll(".showBtn").forEach((btn) => {
            btn.addEventListener("click", () => showModal(btn, modal));
        });

        document.querySelectorAll(".verifyRefund").forEach((btn) => {
            btn.addEventListener("click", () => refunded(btn, getList));
        });
    }
    document.getElementById("selectAll").addEventListener("change", (e) => {
        const checkboxes = document.querySelectorAll(".selectSale");
        checkboxes.forEach((checkbox) => {
            checkbox.checked = e.target.checked;
        });
    });

    document
        .getElementById("refundSelectedBtn")
        .addEventListener("click", async () => {
            const selectedIds = [];
            document
                .querySelectorAll(".selectSale:checked")
                .forEach((checkbox) => {
                    selectedIds.push(checkbox.dataset.id);
                });

            if (selectedIds.length === 0) {
                alert("Please select at least one item to refund.");
                return;
            }

            try {
                const response = await fetch("/full/refunds", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute("content"),
                    },
                    body: JSON.stringify({ ids: selectedIds }),
                });

                const result = await response.json();
                if (response.ok) {
                    alert("Refund successfully processed for selected items.");
                    getList(); // Reload the list
                } else {
                    alert(`Error: ${result.message}`);
                }
            } catch (error) {
                console.error("Error sending refund request:", error);
                alert("An error occurred. Please try again.");
            }
        });

    // Initialize the page
    async function initializePage() {
        initializeModalEvents();
        await fetchShips();
        await fetchCompanies();
        getList();
    }

    initializePage();
});