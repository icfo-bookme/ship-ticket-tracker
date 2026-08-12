document.addEventListener("DOMContentLoaded", () => {
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
    const totalElements = {
        total_number_of_tickets: document.getElementById("totalSellTickets"),
        total_ticket_fee: document.getElementById("totalSoldTicketsAmount"),
        total_other_fee: document.getElementById("totalOtherFees"),
        total_payable: document.getElementById("totalSold"),
        total_refunded_tickets: document.getElementById("totalRefundedTickets"),
        total_refunded_amount: document.getElementById("totalRefundedAmount"),
    };

    let dataTable = null;

    function formatDate(dateString) {
        if (!dateString || dateString === "Not specified") return dateString || "N/A";

        return new Date(dateString).toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    }

    function formatCurrency(amount) {
        if (!amount) return "0.00";

        return new Intl.NumberFormat("en-US", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
    }

    async function loadDropdown(url, selectElement, defaultText) {
        if (!selectElement) return;

        try {
            const response = await fetch(url);
            const data = await response.json();

            selectElement.innerHTML = "";
            selectElement.add(new Option(defaultText, ""));
            data.forEach((item) => selectElement.add(new Option(item.name, item.id)));
        } catch (error) {
            console.error(`Error loading ${defaultText.toLowerCase()}:`, error);
        }
    }

    function filterElements() {
        return [
            shipFilter,
            companyFilter,
            journeyDateFilter,
            returnDateFilter,
            paymentMethodFilter,
            startDateFilter,
            endDateFilter,
            createdDateFilter,
            startCreateDateFilter,
            endCreateDateFilter,
        ];
    }

    function getFilters() {
        return {
            ship_id: shipFilter?.value || "",
            company_id: companyFilter?.value || "",
            journey_date: journeyDateFilter?.value || "",
            return_date: returnDateFilter?.value || "",
            payment_method: paymentMethodFilter?.value || "",
            start_date: startDateFilter?.value || "",
            end_date: endDateFilter?.value || "",
            created_date: createdDateFilter?.value || "",
            start_create_date: startCreateDateFilter?.value || "",
            end_create_date: endCreateDateFilter?.value || "",
        };
    }

    function reloadTable() {
        if (dataTable) {
            dataTable.ajax.reload();
            return;
        }

        initDataTable();
    }

    function updateTotals(totals = {}) {
        Object.entries(totalElements).forEach(([key, element]) => {
            if (element) element.textContent = totals[key] || (key.includes("tickets") ? "0" : "0.00");
        });
    }

    function initDataTable() {
        if (!document.getElementById("salesTable")) {
            console.error("salesTable element not found");
            return;
        }

        if (loader) loader.style.display = "block";
        if (salesBody) salesBody.innerHTML = "";
        if (table) table.classList.remove("hidden");

        dataTable = $("#salesTable").DataTable({
            processing: true,
            serverSide: true,
            ordering: false,
            ajax: {
                url: "/reports",
                type: "GET",
                data: (request) => {
                    Object.entries(getFilters()).forEach(([key, value]) => {
                        if (value) request[key] = value;
                    });
                },
                dataSrc: (json) => {
                    updateTotals(json.totals);
                    return json.data || [];
                },
                error: (xhr, error) => {
                    console.error("AJAX error:", error);
                    if (loader) loader.textContent = "Failed to load data from server.";
                    return [];
                },
            },
            columns: [
                { data: "id", title: "ID", render: (data) => data || "N/A" },
                { data: "customer_name", title: "Customer Name", render: (data) => data || "N/A" },
                { data: "customer_mobile", title: "Mobile", render: (data) => data || "N/A" },
                { data: "ship_name", title: "Ship Name", render: (data) => data || "N/A" },
                { data: "journey_date", title: "Journey Date", render: formatDate },
                { data: "number_of_ticket", title: "Number Of Ticket", render: (data) => data || "0" },
                { data: "ticket_fee", title: "Total Ticket Price", render: formatCurrency },
                { data: "other_fee", title: "Other Fee", render: formatCurrency },
                { data: "total_payable", title: "Total Payable", render: formatCurrency },
                { data: "received_amount", title: "Received Amount", render: formatCurrency },
                { data: "refunded_number_of_tickets", title: "Refunded Tickets", render: (data) => data || 0 },
                { data: "refunded_amount", title: "Refunded Amount", render: formatCurrency },
                { data: "due_amount", title: "Due Amount", render: formatCurrency },
                { data: null, title: "Action", orderable: false, searchable: false, render: (data, type, row) => createActionButtons(row) },
            ],
            dom: "lBfrtip",
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
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
                    previous: "Previous",
                },
            },
            buttons: ["copy", "excel", "csv", "pdf", "print", { extend: "colvis", text: "Column Visibility" }],
            initComplete: () => {
                if (loader) loader.style.display = "none";
            },
            error: (error) => {
                console.error("DataTables error:", error);
                if (loader) loader.textContent = "Failed to load data. Please try again.";
            },
        });
    }

    function createActionButtons(row) {
        if (!row?.id) return "";

        return `
            <div class="flex gap-2 items-center justify-center">
                <a href="/ship-ticket-sales/${row.id}">
                    <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
                </a>
            </div>`;
    }

    function bindEvents() {
        filterElements().forEach((filter) => filter?.addEventListener("change", reloadTable));

        clearFiltersBtn?.addEventListener("click", () => {
            filterElements().forEach((filter) => {
                if (filter) filter.value = "";
            });
            reloadTable();
        });
    }

    async function initializePage() {
        bindEvents();
        await Promise.all([
            loadDropdown("/ships", shipFilter, "All Ships"),
            loadDropdown("/companies", companyFilter, "All Companies"),
        ]);
        initDataTable();
    }

    initializePage();
});
