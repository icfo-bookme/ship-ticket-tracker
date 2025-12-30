document.addEventListener("DOMContentLoaded", () => {
    const loader = document.getElementById("loader");
    const table = document.getElementById("salesTable");
    const salesBody = document.getElementById("salesBody");
    const shipFilter = document.getElementById("shipFilter");
    const companyFilter = document.getElementById("companyFilter");
    const journeyDateFilter = document.getElementById("journeyDateFilter");
    const clearFiltersBtn = document.getElementById("clearFilters");
    const totalRefundedTicketsElement = document.getElementById("totalRefundedTickets");
    const totalRefundedAmountElement = document.getElementById("totalRefundedAmount");

    let dataTableInitialized = false;
    let dataTable;
    let ships = [];
    let companies = [];

 

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

    function createActionButtons(row) {
        return `
            <button class="text-white bg-yellow-700 px-2 py-1 rounded editRefundedBtn" 
                data-id="${row.refund.id}"
                data-received_total_amount="${row.received_amount}"
                data-number_ticket="${row.number_of_ticket}"
                data-refunded_amount="${row.refund.refunded_amount}"
                data-refunded_number_of_tickets="${row.refund.refunded_number_of_tickets}">
                Edit Refunded
            </button>
            <a href="/ship-ticket-sales/${row.id}">
                <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
            </a>
            <button class="fas fa-trash text-red-500 px-2 py-1 rounded deleteBtn" data-id="${row.id}"></button>
        `;
    }

    function formatDate(dateString) {
        if (!dateString || dateString === "Not specified") return dateString;
        
        try {
            return new Date(dateString).toLocaleDateString("en-US", {
                year: "numeric",
                month: "long",
                day: "numeric",
            });
        } catch (error) {
            return dateString;
        }
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
            if (loader) loader.style.display = "block";

            const selectedShipId = shipFilter.value;
            const selectedCompanyId = companyFilter.value;
            const selectedJourneyDate = journeyDateFilter.value;

            // Destroy existing DataTable if initialized
            if (dataTableInitialized && dataTable) {
                dataTable.destroy();
                dataTableInitialized = false;
            }

            // Clear table body
            if (salesBody) salesBody.innerHTML = "";

            if (loader) loader.style.display = "none";
            if (table) table.classList.remove("hidden");

            dataTable = $("#salesTable").DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                ajax: {
                    url: "/all/refunded",
                    type: 'GET',
                    data: function (d) {
                        d.ship_id = selectedShipId;
                        d.company_id = selectedCompanyId;
                        d.journey_date = selectedJourneyDate;
                    },
                    dataSrc: function (json) {
                        // Update totals from server response
                        if (totalRefundedTicketsElement && json.total_refunded_tickets !== undefined) {
                            totalRefundedTicketsElement.textContent = json.total_refunded_tickets;
                        }
                        if (totalRefundedAmountElement && json.total_refunded_amount !== undefined) {
                            totalRefundedAmountElement.textContent = json.total_refunded_amount;
                        }
                        
                        return json.data;
                    }
                },
                columns: [
                    { 
                        data: 'id',
                        render: function(data, type, row) {
                            return data || 'N/A';
                        }
                    },
                    { 
                        data: 'customer_name',
                        render: function(data, type, row) {
                            return data || 'N/A';
                        }
                    },
                    { 
                        data: 'customer_mobile',
                        render: function(data, type, row) {
                            return data || 'N/A';
                        }
                    },
                    {
                        data: null,
                        render: function (data, type, row) {
                            return row.ship?.name || row.ships?.name || 'Not available';
                        }
                    },
                    {
                        data: 'journey_date',
                        render: function (data) {
                            return formatDate(data);
                        }
                    },
                    { 
                        data: 'number_of_ticket',
                        render: function(data, type, row) {
                            return data || 0;
                        }
                    },
                    {
                        data: 'refund.refunded_number_of_tickets',
                        render: function (data, type, row) {
                            return row.refund?.refunded_number_of_tickets || 0;
                        }
                    },
                    { 
                        data: 'received_amount',
                        render: function(data, type, row) {
                            return data || 0;
                        }
                    },
                    {
                        data: 'refund.refunded_amount',
                        render: function (data, type, row) {
                            return row.refund?.refunded_amount || 0;
                        }
                    },
                    { 
                        data: 'status',
                        render: function(data, type, row) {
                            return data || 'N/A';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
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
                },
                error: function (xhr, error, thrown) {
                    console.error("DataTables error:", error);
                    if (loader) {
                        loader.textContent = "Failed to load data. Please try again later.";
                    }
                }
            });

            dataTableInitialized = true;

        } catch (error) {
            console.error("Error initializing DataTable:", error);
            if (loader) {
                loader.textContent = "Failed to load data. Please try again later.";
            }
        }
    }

    function attachEventListeners() {
        // Delete button event listeners
        document.querySelectorAll(".deleteBtn").forEach((btn) => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                deleteSale(btn, getList);
            });
        });

        // Edit refunded button event listeners
        document.querySelectorAll(".editRefundedBtn").forEach((btn) => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                refunded(btn, getList);
            });
        });
    }

    // Delete sale function
    async function deleteSale(button, callback) {
        const saleId = button.getAttribute('data-id');
        
        if (!confirm('Are you sure you want to delete this sale?')) {
            return;
        }

        try {
            const response = await fetch(`/ship-ticket-sales/${saleId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok) {
                alert('Sale deleted successfully');
                if (callback && typeof callback === 'function') {
                    callback();
                }
            } else {
                alert('Error deleting sale: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error deleting sale:', error);
            alert('Error deleting sale. Please try again.');
        }
    }


    // Initialize the page
    async function initializePage() {
        await fetchShips();
        await fetchCompanies();
        getList();
    }

    initializePage();
});