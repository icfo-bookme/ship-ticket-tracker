@php
    $pendingStatus = \App\Enums\SaleStatus::Pending->value;
    $paymentVerifiedStatus = \App\Enums\SaleStatus::PaymentVerified->value;
    $ticketIssuedStatus = \App\Enums\SaleStatus::TicketIssued->value;
    $ticketPrintedStatus = \App\Enums\SaleStatus::TicketPrinted->value;
    $shipmentIdEnteredStatus = \App\Enums\SaleStatus::ShipmentIdEntered->value;
    $shippedStatus = \App\Enums\SaleStatus::Shipped->value;
@endphp

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
    <div class="flex items-center justify-between py-6">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ship Ticket Sales ({{ $status }} )
        </h2>
    </div>
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="mt-6 mb-4 grid grid-cols-4 gap-10">
        <div>
            <label for="companyFilter" class="block text-sm font-medium text-gray-700">Filter by Source
                Company</label>
            <select id="companyFilter"
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="">All Companies</option>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="shipFilter" class="block text-sm font-medium text-gray-700">Filter by Ship</label>
            <select id="shipFilter"
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="">All Ships</option>
                @foreach ($ships as $ship)
                    <option value="{{ $ship->id }}">{{ $ship->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex-1">
            <label for="journeyDateFilter" class="block text-sm font-medium text-gray-700">Filter by Journey
                Date</label>
            <input type="date" id="journeyDateFilter"
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <div class="flex items-end">
            <button id="clearFilters"
                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                Clear Filters
            </button>
        </div>
    </div>

    <script>
        const shipFilter = document.getElementById("shipFilter");
        const companyFilter = document.getElementById("companyFilter");
        const journeyDateFilter = document.getElementById("journeyDateFilter");
        const clearFiltersBtn = document.getElementById("clearFilters");

        window.dataTableColumns = window.dataTableColumns || {};
        window.dataTableColumns['salesTable'] = [
            {
                data: "id",
                render: copyCell,
            },
            {
                data: "customer_name",
                render: copyCell,
            },
            {
                data: "customer_mobile",
                render: copyCell,
            },
            {
                data: "whatsapp",
                render: copyCell,
            },
            {
                data: null,
                render: (row) => escapeHtml(row.ship?.name || row.ships?.name || "Not available"),
            },
            @if ($status == $shipmentIdEnteredStatus)
                {
                    data: "shipment.shipment_id",
                    render: (data) => escapeHtml(data ?? "Not available"),
                },
            @endif
            @if ($status == $pendingStatus)
                {
                    data: "payments",
                    title: "Transaction ID",
                    orderable: false,
                    searchable: false,
                    render: renderTransactionIds,
                },
                {
                    data: "received_amount",
                    render: (data) => escapeHtml(data ?? "Not available"),
                },
                {
                    data: "payments",
                    title: "Payment Info",
                    orderable: false,
                    searchable: false,
                    render: renderPayments,
                },
                {
                    data: "discount_amount",
                    render: (data) => escapeHtml(Number(data) > 0 ? Number(data).toFixed(2) : "0.00"),
                },
                {
                    data: "payments",
                    title: "Payment Proof",
                    orderable: false,
                    searchable: false,
                    render: renderPaymentProofs,
                },
            @endif
            {
                data: null,
                orderable: false,
                render: (data, type, row) => createActionButtons(row),
            },
        ];

        window.dataTableFilters = window.dataTableFilters || {};
        window.dataTableFilters['salesTable'] = () => ({
            ship_id: shipFilter.value,
            company_id: companyFilter.value,
            journey_date: journeyDateFilter.value,
        });

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
            toast.className =
                "fixed bottom-4 right-4 bg-black text-white px-4 py-2 rounded shadow-lg opacity-0 transition-opacity duration-300 z-50";
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

        function renderPayments(payments) {
            if (!payments?.length) {
                return '<span class="text-gray-400 text-sm">Not paid yet</span>';
            }

            const items = payments.map((payment) => {
                const proofLink = payment.payment_proof
                    ? ` <a href="/payments/${payment.id}/proof" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs" title="Payment proof"><i class="fas fa-paperclip"></i> proof</a>`
                    : "";

                return `
                <li>
                    <span class="font-medium">${escapeHtml(payment.payment_method)}</span> :
                    <span class="text-green-600 font-semibold">${escapeHtml(payment.received_amount)}</span>${proofLink}
                </li>`;
            });

            return `<ul class="space-y-1 text-sm">${items.join("")}</ul>`;
        }

        function renderPaymentProofs(payments) {
            const proofs = (payments || []).filter((payment) => payment.payment_proof);

            if (!proofs.length) {
                return '<span class="text-gray-400 text-sm">N/A</span>';
            }

            const thumbs = proofs.map((payment) => `
                <button type="button" class="paymentProofBtn" data-proof-url="/payments/${payment.id}/proof"
                    title="Click to view payment proof">
                    <img src="/payments/${payment.id}/proof" alt="Payment proof" loading="lazy"
                        class="h-10 w-10 object-cover rounded border border-gray-300 hover:ring-2 hover:ring-blue-400 transition">
                </button>`);

            return `<div class="flex flex-wrap items-center justify-center gap-1">${thumbs.join("")}</div>`;
        }

        function openPaymentProof(button) {
            const modal = document.getElementById("proofImageModal");
            if (!modal) return;

            document.getElementById("proofImageModalImg").src = button.dataset.proofUrl;
            modal.classList.remove("hidden");
            modal.classList.add("flex");
        }

        function renderTransactionIds(payments) {
            const transactionIds = (payments || [])
                .map((payment) => payment.transaction_id)
                .filter((transactionId) => transactionId);

            if (!transactionIds.length) {
                return '<span class="text-gray-400 text-sm">N/A</span>';
            }

            return `<div class="flex flex-col items-center gap-1">${transactionIds.map((transactionId) => transactionIdBadge(transactionId)).join("")}</div>`;
        }

        function transactionIdBadge(transactionId) {
            const escaped = escapeHtml(transactionId);

            return `
                <div class="flex items-center gap-2 justify-center">
                    <span class="transactionIdBadge bg-red-500 text-white px-2 py-1 rounded text-sm font-semibold">${escaped}</span>
                    <button class="copyBtn text-gray-500 hover:text-blue-950" data-copy="${escaped}" title="Copy">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>`;
        }

        function createActionButtons(sale) {
            @can('payments.manage')
                const dueButton = Number(sale.due_amount) > 0
                    ? `<button class="bg-yellow-500 text-black px-2 py-1 rounded dueBtn"
                        data-id="${sale.id}"
                        data-due_amount="${sale.due_amount}"
                        title="Due Amount: ${escapeHtml(sale.due_amount)}">Due</button>`
                    : "";
            @else
                const dueButton = "";
            @endcan

            @can('sales.edit')
                const editButton = `<a href="/ship-ticket-sales/${sale.id}">
                        <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
                    </a>`;
            @else
                const editButton = "";
            @endcan

            @can('sales.delete')
                const deleteButton = `<button class="fas fa-trash text-red-500 px-2 py-1 border border-gray-300 rounded deleteBtn"
                        data-id="${sale.id}" title="Delete"></button>`;
            @else
                const deleteButton = "";
            @endcan

            return `
                <div class="flex gap-2 items-center justify-center">
                    ${editButton}
                    ${deleteButton}
                    ${dueButton}
                    ${createStatusButton(sale)}
                </div>`;
        }

        function createStatusButton(sale) {
            @cannot('sales.verify')
                return "";
            @endcannot

            const verifiedBy = escapeHtml(sale.verifyby?.[0]?.verified_by_user?.name || "Unknown");
            const printedFiles = sale.grouped_tickets || [];

            if (sale.status === @js($pendingStatus)) {
                return `<button class="bg-red-500 text-white px-2 py-1 rounded verifyBtn"
                    data-id="${sale.id}" data-status="{{ $paymentVerifiedStatus }}"
                    title="Sold by: ${escapeHtml(sale.sold_by)}">Verify Payment</button>`;
            }

            if (sale.status === @js($ticketIssuedStatus)) {
                return printedFiles.length
                    ? statusButton(sale.id, @js($ticketPrintedStatus), "Ticket Printed", `Ticket Issued by: ${verifiedBy}`)
                        + printedFileRows(sale, printedFiles)
                    : referenceBy(sale);
            }

            if (sale.status === @js($ticketPrintedStatus)) {
                return printedFiles.length
                    ? statusButton(
                        sale.id,
                        @js($shipmentIdEnteredStatus),
                        "Add To Parcel",
                        `ticket-printed by: ${verifiedBy}`,
                        "shipmentIdEntryBtn",
                    ) + printedFileRows(sale, printedFiles)
                    : referenceBy(sale);
            }

            if (sale.status === @js($shipmentIdEnteredStatus)) {
                return statusButton(sale.id, @js($shippedStatus), "Shipped", `shipment_id_entered by: ${verifiedBy}`);
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

        function bindSalesTableEvents() {
            const table = document.getElementById("salesTable");

            [shipFilter, companyFilter, journeyDateFilter].forEach((filter) => {
                filter?.addEventListener("change", () => window.getList());
            });

            clearFiltersBtn?.addEventListener("click", () => {
                [shipFilter, companyFilter, journeyDateFilter].forEach((filter) => {
                    if (filter) filter.value = "";
                });
                window.getList();
            });

            table?.addEventListener("click", (event) => {
                const button = event.target.closest("button");
                if (!button) return;

                if (button.classList.contains("copyBtn")) {
                    event.stopPropagation();
                    copyToClipboard(button.dataset.copy);
                } else if (button.classList.contains("verifyBtn")) {
                    varifySale(button, window.getList);
                } else if (button.classList.contains("deleteBtn")) {
                    deleteSale(button, window.getList);
                } else if (button.classList.contains("shipmentIdEntryBtn")) {
                    varifyShipment(button, window.getList);
                } else if (button.classList.contains("dueBtn")) {
                    due(button, window.getList);
                } else if (button.classList.contains("paymentProofBtn")) {
                    openPaymentProof(button);
                }
            });

            document.addEventListener("keydown", (event) => {
                if (event.key !== "Escape") return;

                const modal = document.getElementById("proofImageModal");
                if (modal && !modal.classList.contains("hidden")) {
                    modal._closeModal();
                }
            });
        }

        document.addEventListener("DOMContentLoaded", bindSalesTableEvents);
    </script>

    <x-data-table id="salesTable" :headings="array_values(array_filter([
        'ID',
        'Customer Name',
        'Mobile',
        'WhatsApp',
        'Ship Name',
        $status == $shipmentIdEnteredStatus ? 'Shipment Id' : null,
        $status == $pendingStatus ? 'Transaction ID' : null,
        $status == $pendingStatus ? 'Total Received Amount' : null,
        $status == $pendingStatus ? 'Payment Methods' : null,
        $status == $pendingStatus ? 'Discount Amount' : null,
        $status == $pendingStatus ? 'Payment Proof' : null,
        'Action',
    ]))" :url="'/sales/' . $status" :ordering="false" :delegateActions="false" :order="[]"
        :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />

    <x-entity-modal id="proofImageModal" title="Payment Proof" maxWidth="2xl" :hideFooter="true">
        <div class="p-4 flex items-center justify-center bg-gray-50 dark:bg-gray-800">
            <img id="proofImageModalImg" src="" alt="Payment proof"
                class="max-h-[75vh] w-auto max-w-full rounded shadow" />
        </div>
    </x-entity-modal>
</div>
