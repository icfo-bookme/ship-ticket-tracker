document.addEventListener("DOMContentLoaded", () => {
    const loader = document.getElementById("loader");
    const table = document.getElementById("salesTable");
    const salesBody = document.getElementById("salesBody");
    const shipFilter = document.getElementById("shipFilter");
    const companyFilter = document.getElementById("companyFilter");
    const journeyDateFilter = document.getElementById("journeyDateFilter");
    const clearFiltersBtn = document.getElementById("clearFilters");
    const selectAll = document.getElementById("selectAll");
    const refundSelectedBtn = document.getElementById("refundSelectedBtn");

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
        if (!dateString) return "N/A";

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
            if (selectAll) selectAll.checked = false;
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
                url: "/all/refundable",
                type: "GET",
                data: (request) => Object.assign(request, getFilters()),
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => `<input type="checkbox" class="selectSale" data-id="${row.id}" />`,
                },
                { data: "id" },
                { data: "customer_name", render: escapeHtml },
                { data: "customer_mobile", render: escapeHtml },
                { data: null, render: (row) => escapeHtml(row.ship?.name || row.ships?.name || "Not available") },
                { data: "journey_date", render: formatDate },
                { data: "number_of_ticket" },
                { data: "ticket_fee" },
                { data: "other_fee" },
                { data: "total_payable" },
                { data: "received_amount" },
                { data: "due_amount" },
                { data: null, orderable: false, render: (data, type, row) => createActionButtons(row) },
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

    function createActionButtons(sale) {
        return `
            <div class="flex gap-2 items-center justify-center">
                <a href="/ship-ticket-sales/${sale.id}">
                    <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
                </a>
                <button class="bg-blue-900 text-white px-2 py-1 rounded verifyRefund"
                    data-id="${sale.id}"
                    data-received_total_amount="${sale.received_amount}"
                    data-number_ticket="${sale.number_of_ticket}"
                    data-status="shipped">
                    Partial Refund
                </button>
            </div>`;
    }

    function selectedSaleIds() {
        return Array.from(document.querySelectorAll(".selectSale:checked"))
            .map((checkbox) => checkbox.dataset.id);
    }

    async function refundSelectedSales() {
        const ids = selectedSaleIds();
        if (!ids.length) {
            Swal.fire({ title: "Error!", text: "Please select at least one item to refund.", icon: "error", confirmButtonText: "OK" });
            return;
        }

        try {
            const response = await fetch("/full/refunds", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                },
                body: JSON.stringify({ ids }),
            });
            const result = await response.json();

            if (result.status === "success") {
                Swal.fire({ title: "Success!", text: "Refund successfully processed for selected items.", icon: "success", confirmButtonText: "OK" });
                reloadTable();
                return;
            }

            Swal.fire({ title: "Error!", text: result.message || "Refund failed.", icon: "error", confirmButtonText: "OK" });
        } catch (error) {
            console.error("Error sending refund request:", error);
            Swal.fire({ title: "Error!", text: "An error occurred. Please try again.", icon: "error", confirmButtonText: "OK" });
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

        selectAll?.addEventListener("change", () => {
            document.querySelectorAll(".selectSale").forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
        });

        refundSelectedBtn?.addEventListener("click", refundSelectedSales);

        table?.addEventListener("click", (event) => {
            const button = event.target.closest(".verifyRefund");
            if (button) refunded(button, reloadTable);
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
