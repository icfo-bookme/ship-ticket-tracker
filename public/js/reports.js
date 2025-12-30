document.addEventListener("DOMContentLoaded", () => {
    // Safely get all elements with null checks
    const loader = document.getElementById("loader");
    const table = document.getElementById("salesTable");
    const salesBody = document.getElementById("salesBody");
    const shipFilter = document.getElementById("shipFilter");
    const companyFilter = document.getElementById("companyFilter");
    const journeyDateFilter = document.getElementById("journeyDateFilter");
    const returnDateFilter = document.getElementById("returnDateFilter");
    const clearFiltersBtn = document.getElementById("clearFilters");
    const paymentMethodFilter = document.getElementById("payment_method");
    const startDateFilter = document.getElementById("startDate");
    const endDateFilter = document.getElementById("endDate");
    const createdDateFilter = document.getElementById("createdDateFilter");
    const startCreateDateFilter = document.getElementById("startCreateDate");
    const endCreateDateFilter = document.getElementById("endCreateDate");

    // Totals elements with null checks
    const totalRefundedTickets = document.getElementById("totalRefundedTickets");
    const totalRefundedAmount = document.getElementById("totalRefundedAmount");
    const totalSellTickets = document.getElementById("totalSellTickets");
    const totalSellAmount = document.getElementById("totalSellAmount");

    let dataTableInitialized = false;
    let dataTable;
    let ships = [];
    let companies = [];

    async function fetchShips() {
        try {
            const response = await fetch("/ships");
            ships = await response.json();
            if (shipFilter) {
                populateDropdown(shipFilter, ships, "All Ships");
            }
        } catch (error) {
            console.error("Error fetching ships:", error);
        }
    }

    async function fetchCompanies() {
        try {
            const response = await fetch("/companies");
            companies = await response.json();
            if (companyFilter) {
                populateDropdown(companyFilter, companies, "All Companies");
            }
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

    function formatCurrency(amount) {
        if (!amount) return '0.00';
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }

    // Safely add event listeners only if elements exist
    function initializeEventListeners() {
        const elements = [
            { element: shipFilter, event: 'change' },
            { element: companyFilter, event: 'change' },
            { element: journeyDateFilter, event: 'change' },
            { element: returnDateFilter, event: 'change' },
            { element: paymentMethodFilter, event: 'change' },
            { element: startDateFilter, event: 'change' },
            { element: endDateFilter, event: 'change' },
            { element: createdDateFilter, event: 'change' },
            { element: startCreateDateFilter, event: 'change' },
            { element: endCreateDateFilter, event: 'change' }
        ];

        elements.forEach(({ element, event }) => {
            if (element) {
                element.addEventListener(event, getList);
            }
        });

        // Clear filters with safe element access
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener("click", () => {
                const filters = [
                    shipFilter, companyFilter, journeyDateFilter, returnDateFilter,
                    paymentMethodFilter, startDateFilter, endDateFilter, createdDateFilter,
                    startCreateDateFilter, endCreateDateFilter
                ];
                
                filters.forEach(filter => {
                    if (filter) filter.value = "";
                });
                
                getList();
            });
        }
    }

    async function getList() {
        try {
            // Safe loader handling
            if (loader) {
                loader.style.display = "block";
            }

            const filters = {
                ship_id: shipFilter ? shipFilter.value : "",
                company_id: companyFilter ? companyFilter.value : "",
                journey_date: journeyDateFilter ? journeyDateFilter.value : "",
                return_date: returnDateFilter ? returnDateFilter.value : "",
                payment_method: paymentMethodFilter ? paymentMethodFilter.value : "",
                start_date: startDateFilter ? startDateFilter.value : "",
                end_date: endDateFilter ? endDateFilter.value : "",
                created_date: createdDateFilter ? createdDateFilter.value : "",
                start_create_date: startCreateDateFilter ? startCreateDateFilter.value : "",
                end_create_date: endCreateDateFilter ? endCreateDateFilter.value : "",
            };

            // Destroy existing DataTable if initialized
            if (dataTableInitialized && dataTable) {
                dataTable.destroy();
                dataTableInitialized = false;
            }

            // Clear table body if it exists
            if (salesBody) {
                salesBody.innerHTML = "";
            }

            // Safe DOM manipulation
            if (loader) {
                loader.style.display = "none";
            }
            if (table) {
                table.classList.remove("hidden");
            }

            // Check if table exists before initializing DataTable
            if (!document.getElementById('salesTable')) {
                console.error('salesTable element not found');
                return;
            }

            // Initialize DataTable with server-side processing
            dataTable = $("#salesTable").DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                ajax: {
                    url: "/reports",
                    type: 'GET',
                    data: function (d) {
                        // Add custom filters to DataTables request
                        Object.keys(filters).forEach(key => {
                            if (filters[key]) {
                                d[key] = filters[key];
                            }
                        });
                    },
                    dataSrc: function (json) {
                        // Safe totals update - match your HTML structure
                        if (json.totals) {
                            if (totalSellTickets) totalSellTickets.textContent = json.totals.total_sold_tickets || '0';
                            if (totalSellAmount) totalSellAmount.textContent = json.totals.total_sales_amount || '0.00';
                            if (totalRefundedTickets) totalRefundedTickets.textContent = json.totals.total_refunded_tickets || '0';
                            if (totalRefundedAmount) totalRefundedAmount.textContent = json.totals.total_refunded_amount || '0.00';
                        }
                        
                        // Return data or empty array
                        return json.data || [];
                    },
                    error: function (xhr, error, thrown) {
                        console.error('AJAX error:', error);
                        if (loader) {
                            loader.textContent = "Failed to load data from server.";
                        }
                        return [];
                    }
                },
                columns: [
                    { 
                        data: 'id',
                        title: 'ID',
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    { 
                        data: 'customer_name',
                        title: 'Customer Name',
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    { 
                        data: 'customer_mobile',
                        title: 'Mobile',
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    { 
                        data: 'ship_name',
                        title: 'Ship Name',
                        render: function(data) {
                            return data || 'N/A';
                        }
                    },
                    {
                        data: 'journey_date',
                        title: 'Journey Date',
                        render: function (data) {
                            return formatDate(data);
                        }
                    },
                    { 
                        data: 'number_of_ticket',
                        title: 'Number Of Ticket',
                        render: function(data) {
                            return data || '0';
                        }
                    },
                    { 
                        data: 'received_amount',
                        title: 'Total Ticket Price',
                        render: function (data) {
                            return formatCurrency(data);
                        }
                    },
                    { 
                        data: 'received_amount',
                        title: 'Received Amount',
                        render: function (data) {
                            return formatCurrency(data);
                        }
                    },
                    {
                        data: null,
                        title: 'Action',
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row) {
                            return createActionButtons(row);
                        }
                    }
                ],
                dom: "lBfrtip",
                lengthChange: true,
                lengthMenu: [
                    [10, 25, 50, 100],
                    [10, 25, 50, 100]
                ],
                language: {
                    processing: "Processing...",
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    loadingRecords: "Loading...",
                    zeroRecords: "No matching records found",
                    emptyTable: "No data available in table",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                buttons: [
                    "copy",
                    "excel", 
                    "csv",
                    "pdf",
                    "print",
                    {
                        extend: "colvis",
                        text: "Column Visibility",
                    }
                ],
                
                error: function (xhr, error, thrown) {
                    console.error("DataTables error:", error);
                    if (loader) {
                        loader.textContent = "Failed to load data. Please try again.";
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

    function createActionButtons(row) {
        if (!row || !row.id) return '';
        
        return `
            <div class="flex gap-2 items-center justify-center">
                <a href="/ship-ticket-sales/${row.id}">
                    <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
                </a>
                
            </div>
        `;
    }

    


  

    // Initialize the page
    async function initializePage() {
        try {
            initializeEventListeners();
            await fetchShips();
            await fetchCompanies();
            getList();
        } catch (error) {
            console.error("Error initializing page:", error);
        }
    }

    initializePage();
});