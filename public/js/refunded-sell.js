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

    let dataTable = null;

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatDate(dateString) {
        if (!dateString || dateString === "Not specified") return dateString || "N/A";

        return new Date(dateString).toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
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

    function getFilters() {
        return {
            ship_id: shipFilter.value,
            company_id: companyFilter.value,
            journey_date: journeyDateFilter.value,
        };
    }

    function reloadTable() {
        if (dataTable) {
            dataTable.ajax.reload();
            return;
        }

        initDataTable();
    }

    function initDataTable() {
        if (loader) loader.style.display = "block";
        if (salesBody) salesBody.innerHTML = "";
        if (table) table.classList.remove("hidden");

        dataTable = $("#salesTable").DataTable({
            processing: true,
            serverSide: true,
            ordering: false,
            ajax: {
                url: "/all/refunded",
                type: "GET",
                data: (request) => Object.assign(request, getFilters()),
                dataSrc: (json) => {
                    if (totalRefundedTicketsElement && json.total_refunded_tickets !== undefined) {
                        totalRefundedTicketsElement.textContent = json.total_refunded_tickets;
                    }
                    if (totalRefundedAmountElement && json.total_refunded_amount !== undefined) {
                        totalRefundedAmountElement.textContent = json.total_refunded_amount;
                    }

                    return json.data || [];
                },
            },
            columns: [
                { data: "id", render: (data) => data || "N/A" },
                { data: "customer_name", render: (data) => data ? escapeHtml(data) : "N/A" },
                { data: "customer_mobile", render: (data) => data ? escapeHtml(data) : "N/A" },
                { data: null, render: (row) => escapeHtml(row.ship?.name || row.ships?.name || "Not available") },
                { data: "journey_date", render: formatDate },
                { data: "number_of_ticket", render: (data) => data || 0 },
                { data: "refund.refunded_number_of_tickets", render: (data, type, row) => row.refund?.refunded_number_of_tickets || 0 },
                { data: "received_amount", render: (data) => data || 0 },
                { data: "refund.refunded_amount", render: (data, type, row) => row.refund?.refunded_amount || 0 },
                { data: "status", render: (data) => data || "N/A" },
                { data: null, orderable: false, searchable: false, render: (data, type, row) => createActionButtons(row) },
            ],
            dom: "lBfrtip",
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            buttons: ["copy", "excel", "csv", "pdf", "print", "colvis"],
            initComplete: () => {
                if (loader) loader.style.display = "none";
            },
            error: (error) => {
                console.error("DataTables error:", error);
                if (loader) loader.textContent = "Failed to load data. Please try again later.";
            },
        });
    }

    function createActionButtons(row) {
        return `
            <button class="text-white bg-yellow-700 px-2 py-1 rounded editRefundedBtn"
                data-id="${row.refund?.id ?? ""}"
                data-received_total_amount="${row.received_amount}"
                data-number_ticket="${row.number_of_ticket}"
                data-refunded_amount="${row.refund?.refunded_amount ?? ""}"
                data-refunded_number_of_tickets="${row.refund?.refunded_number_of_tickets ?? ""}">
                Edit Refunded
            </button>
            <a href="/ship-ticket-sales/${row.id}">
                <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
            </a>
            <button class="fas fa-trash text-red-500 px-2 py-1 rounded deleteBtn" data-id="${row.id}"></button>`;
    }

    async function deleteSale(button) {
        const saleId = button.dataset.id;
        const { isConfirmed } = await Swal.fire({
            title: "Are you sure?",
            text: "Do you want to delete this sale?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete",
            cancelButtonText: "Cancel",
        });

        if (!isConfirmed) return;

        try {
            const response = await fetch(`/ship-ticket-sales/${saleId}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    "Content-Type": "application/json",
                },
            });
            const result = await response.json();

            if (response.ok) {
                Swal.fire({ title: "Deleted!", text: "Sale deleted successfully.", icon: "success", confirmButtonText: "OK" });
                reloadTable();
                return;
            }

            Swal.fire({ title: "Error!", text: result.message || "Failed to delete sale.", icon: "error", confirmButtonText: "OK" });
        } catch (error) {
            console.error("Error deleting sale:", error);
            Swal.fire({ title: "Error!", text: "An error occurred while deleting the sale.", icon: "error", confirmButtonText: "OK" });
        }
    }

    function bindEvents() {
        [shipFilter, companyFilter, journeyDateFilter].forEach((filter) => {
            filter?.addEventListener("change", reloadTable);
        });

        clearFiltersBtn?.addEventListener("click", () => {
            [shipFilter, companyFilter, journeyDateFilter].forEach((filter) => {
                if (filter) filter.value = "";
            });
            reloadTable();
        });

        table?.addEventListener("click", (event) => {
            const button = event.target.closest("button");
            if (!button) return;

            if (button.classList.contains("deleteBtn")) {
                event.preventDefault();
                deleteSale(button);
            } else if (button.classList.contains("editRefundedBtn")) {
                event.preventDefault();
                refunded(button, reloadTable);
            }
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
