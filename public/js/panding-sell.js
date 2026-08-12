document.addEventListener("DOMContentLoaded", () => {
    const loader = document.getElementById("loader");
    const table = document.getElementById("salesTable");
    const salesBody = document.getElementById("salesBody");
    const shipFilter = document.getElementById("shipFilter");
    const companyFilter = document.getElementById("companyFilter");
    const journeyDateFilter = document.getElementById("journeyDateFilter");
    const clearFiltersBtn = document.getElementById("clearFilters");
    const status = document.getElementById("statusFilter")?.dataset.status || "pending";

    let dataTable = null;

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function copyCell(value) {
        const escaped = escapeHtml(value);

        return `
            <div class="flex items-center gap-2 justify-center">
                <span>${escaped || "N/A"}</span>
                <button class="copyBtn text-gray-500 hover:text-blue-950" data-copy="${escaped}" title="Copy">
                    <i class="fas fa-copy"></i>
                </button>
            </div>`;
    }

    function showCopiedMessage() {
        const toast = document.createElement("div");
        toast.textContent = "Copied!";
        toast.className = "fixed bottom-4 right-4 bg-black text-white px-4 py-2 rounded shadow-lg opacity-0 transition-opacity duration-300 z-50";
        document.body.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add("opacity-100"));
        setTimeout(() => {
            toast.classList.remove("opacity-100");
            setTimeout(() => toast.remove(), 300);
        }, 1500);
    }

    function copyToClipboard(text) {
        if (!text) return;

        navigator.clipboard.writeText(text)
            .then(showCopiedMessage)
            .catch((error) => console.error("Copy failed:", error));
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

    function buildColumns() {
        const columns = [
            { data: "id", render: copyCell },
            { data: "customer_name", render: copyCell },
            { data: "customer_mobile", render: copyCell },
            { data: "whatsapp", render: copyCell },
            { data: null, render: (row) => escapeHtml(row.ship?.name || row.ships?.name || "Not available") },
        ];

        if (status === "shipment_id_entered") {
            columns.push({
                data: "shipment.shipment_id",
                render: (data) => escapeHtml(data ?? "Not available"),
            });
        }

        if (status === "pending") {
            columns.push(
                { data: "received_amount", render: (data) => escapeHtml(data ?? "Not available") },
                {
                    data: "payments",
                    title: "Payment Info",
                    orderable: false,
                    searchable: false,
                    render: renderPayments,
                }
            );
        }

        columns.push(
            { data: "remark1", render: copyCell },
            { data: "remark2", render: copyCell },
            { data: null, orderable: false, render: (data, type, row) => createActionButtons(row) }
        );

        return columns;
    }

    function renderPayments(payments) {
        if (!payments?.length) {
            return '<span class="text-gray-400 text-sm">Not paid yet</span>';
        }

        const items = payments.map((payment) => `
            <li>
                <span class="font-medium">${escapeHtml(payment.payment_method)}</span> :
                <span class="text-green-600 font-semibold">${escapeHtml(payment.received_amount)}</span>
            </li>`);

        return `<ul class="space-y-1 text-sm">${items.join("")}</ul>`;
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
                url: `/sales/${status}`,
                type: "GET",
                data: (request) => Object.assign(request, getFilters()),
            },
            columns: buildColumns(),
            dom: "lBfrtip",
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            buttons: ["copy", "excel", "csv", "pdf", "print", "colvis"],
            initComplete: () => {
                if (loader) loader.style.display = "none";
            },
            error: (error) => {
                console.error("DataTables error:", error);
                if (loader) loader.textContent = "Failed to load data.";
            },
        });
    }

    function createActionButtons(sale) {
        const dueButton = Number(sale.due_amount) > 0
            ? `<button class="bg-yellow-500 text-black px-2 py-1 rounded dueBtn"
                    data-id="${sale.id}"
                    data-due_amount="${sale.due_amount}"
                    title="Due Amount: ${escapeHtml(sale.due_amount)}">Due</button>`
            : "";

        return `
            <div class="flex gap-2 items-center justify-center">
                <a href="/ship-ticket-sales/${sale.id}">
                    <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
                </a>
                <button class="fas fa-trash text-red-500 px-2 py-1 border border-gray-300 rounded deleteBtn"
                    data-id="${sale.id}" title="Delete"></button>
                ${dueButton}
                ${createStatusButton(sale)}
            </div>`;
    }

    function createStatusButton(sale) {
        const verifiedBy = escapeHtml(sale.verifyby?.[0]?.verified_by_user?.name || "Unknown");
        const printedFiles = sale.grouped_tickets || [];

        if (sale.status === "pending") {
            return `<button class="bg-red-500 text-white px-2 py-1 rounded verifyBtn"
                data-id="${sale.id}" data-status="payment-verified"
                title="Sold by: ${escapeHtml(sale.sold_by)}">Verify Payment</button>`;
        }

        if (sale.status === "ticket-issued") {
            return printedFiles.length
                ? statusButton(sale.id, "ticket-printed", "Ticket Printed", `Ticket Issued by: ${verifiedBy}`) + printedFileRows(sale, printedFiles)
                : referenceBy(sale);
        }

        if (sale.status === "ticket-printed") {
            return printedFiles.length
                ? statusButton(sale.id, "shipment_id_entered", "Add To Parcel", `ticket-printed by: ${verifiedBy}`, "shipmentIdEntryBtn") + printedFileRows(sale, printedFiles)
                : referenceBy(sale);
        }

        if (sale.status === "shipment_id_entered") {
            return statusButton(sale.id, "shipped", "Shipped", `shipment_id_entered by: ${verifiedBy}`);
        }

        return "";
    }

    function statusButton(id, statusValue, label, title, className = "verifyBtn") {
        return `<button class="bg-red-500 text-white px-2 py-1 rounded ${className}"
            data-id="${id}" data-status="${statusValue}" title="${title}">${label}</button>`;
    }

    function printedFileRows(sale, files) {
        const rows = files.map((file) => `
            <div class="flex items-center gap-2">
                <a href="/ship-ticket-sales/${file.sales_id}" target="_blank"
                   class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-semibold">
                    ${escapeHtml(file.sales_id)}
                </a>
                <a href="/tickets/open/${sale.id}/${encodeURIComponent(file.filename)}" target="_blank"
                   class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-semibold"
                   title="${escapeHtml(file.filename)}">
                    <i class="fas fa-file-pdf"></i> ${escapeHtml(file.filename)}
                </a>
            </div>`);

        return `<div class="flex flex-col gap-2 mt-2">${rows.join("")}</div>`;
    }

    function referenceBy(sale) {
        const groupId = sale.printed_tickets?.[0]?.group_by_id;
        return groupId
            ? `<p class="text-sm font-semibold text-gray-700">Reference By ${escapeHtml(groupId)}</p>`
            : "";
    }

    function printSale(saleId) {
        if (!saleId) return;

        const iframe = document.createElement("iframe");
        iframe.style.display = "none";
        iframe.src = `/print-pdf/${saleId}`;
        document.body.appendChild(iframe);

        iframe.onload = () => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            setTimeout(() => iframe.remove(), 1000);
        };
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

            if (button.classList.contains("copyBtn")) {
                event.stopPropagation();
                copyToClipboard(button.dataset.copy);
            } else if (button.classList.contains("verifyBtn")) {
                varifySale(button, reloadTable);
            } else if (button.classList.contains("deleteBtn")) {
                deleteSale(button, reloadTable);
            } else if (button.classList.contains("shipmentIdEntryBtn")) {
                varifyShipment(button, reloadTable);
            } else if (button.classList.contains("dueBtn")) {
                due(button, reloadTable);
            } else if (button.classList.contains("printBtn")) {
                printSale(button.dataset.id);
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
