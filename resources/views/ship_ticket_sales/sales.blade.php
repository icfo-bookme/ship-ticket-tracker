@php
    $pendingStatus = \App\Enums\SaleStatus::Pending->value;
    $paymentVerifiedStatus = \App\Enums\SaleStatus::PaymentVerified->value;
    $ticketIssueRouteTemplate = route('ship-ticket-issue.show', ['ship_ticket_sale' => '__SALE_ID__']);
    $ticketIssuedStatus = \App\Enums\SaleStatus::TicketIssued->value;
    $ticketPrintedStatus = \App\Enums\SaleStatus::TicketPrinted->value;
    $shipmentIdEnteredStatus = \App\Enums\SaleStatus::ShipmentIdEntered->value;
    $shippedStatus = \App\Enums\SaleStatus::Shipped->value;
    $collectFromOfficeStatus = \App\Enums\SaleStatus::CollectFromOffice->value;
@endphp

<div class="w-full px-2 sm:px-4 lg:px-6">
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
            <label for="journeyDateFilter" class="block text-sm font-medium text-gray-700">Filter by Departure
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
            @if ($status == $ticketPrintedStatus)
                {
                    data: null,
                    title: "Address",
                    render: (row) => row.collect_from_office
                        ? "Collect from Office"
                        : escapeHtml(row.address || "Not available"),
                },
            @endif
            @if ($status == $paymentVerifiedStatus)
                {
                    data: null,
                    title: "Company",
                    render: (row) => escapeHtml(row.company?.name || row.companies?.name || "Not available"),
                },
            @endif
            @if ($status == $shipmentIdEnteredStatus)
                {
                    data: "shipment.shipment_id",
                    render: (data) => escapeHtml(data ?? "Not available"),
                },
            @endif
            @if ($status == $pendingStatus)
                {
                    data: "ticket_fee",
                    title: "Total Ticket Value",
                    render: (data) => escapeHtml(Number(data || 0).toFixed(2)),
                },
                {
                    data: "other_fee",
                    title: "Other Fee",
                    render: (data) => escapeHtml(Number(data || 0).toFixed(2)),
                },
                {
                    data: "discount_amount",
                    title: "Discount Amount",
                    render: (data) => escapeHtml(Number(data || 0).toFixed(2)),
                },
                {
                    data: "received_amount",
                    title: "Received Amount",
                    render: (data) => escapeHtml(Number(data || 0).toFixed(2)),
                },
                {
                    data: "payments",
                    title: "Transaction ID",
                    orderable: false,
                    searchable: false,
                    render: renderTransactionIds,
                },
                {
                    data: "payments",
                    title: "Payment Info",
                    orderable: false,
                    searchable: false,
                    render: renderPayments,
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

        window.dataTableCreatedRows = window.dataTableCreatedRows || {};
        window.dataTableCreatedRows['salesTable'] = (row, sale) => {
            if (sale.bftn_status === 'yes' && !sale.bftn_received) {
                row.classList.add('bg-amber-300');
                row.querySelectorAll('td').forEach((cell) => {
                    cell.classList.add('!bg-amber-300');
                });
            }
        };

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
                const deleteButton = `<button class="fas fa-trash text-red-500 bg-white px-2 py-1 border border-gray-300 rounded deleteBtn"
                        data-id="${sale.id}" title="Delete"></button>`;
            @else
                const deleteButton = "";
            @endcan

            @can('sales.verify')
                const bftnReceivedButton = sale.bftn_status === 'yes' && !sale.bftn_received
                    ? `<button class="bg-green-600 text-white px-2 py-1 rounded bftnReceivedBtn"
                        data-id="${sale.id}" data-tentative-date="${escapeHtml(sale.bftn?.bftn_date_time || 'Not specified')}" title="Mark BFTN as received">BFTN Received</button>`
                    : "";
            @else
                const bftnReceivedButton = "";
            @endcan

            const activeRefund = (sale.refunds || []).find((refund) => !['cancelled', 'completed'].includes(refund.status));
            const editRefundButton = activeRefund
                ? `<a href="/refund-requests?search=${encodeURIComponent(sale.id)}"
                    class="fas fa-rotate-left text-amber-700 px-2 py-1"
                    title="Edit refund request" aria-label="Edit refund request"></a>`
                : "";

            return `
                <div class="flex gap-2 items-center justify-center">
                    ${editRefundButton}
                    ${editButton}
                    ${deleteButton}
                    ${dueButton}
                    ${bftnReceivedButton}
                    ${createStatusButton(sale)}
                </div>`;
        }

        function createStatusButton(sale) {
            if (sale.status === @js($collectFromOfficeStatus)) {
                return sale.grouped_tickets?.length
                    ? '<span class="text-green-700 font-semibold">Collected</span>'
                    : referenceBy(sale);
            }

            @cannot('sales.verify')
                return "";
            @endcannot

            const verifiedBy = escapeHtml(sale.verifyby?.[0]?.verified_by_user?.name || "Unknown");
            const printedFiles = sale.grouped_tickets || [];

            if (sale.status === @js($paymentVerifiedStatus)) {
                const issueTicketUrl = @js($ticketIssueRouteTemplate).replace('__SALE_ID__', encodeURIComponent(sale.id));

                return `<a href="${issueTicketUrl}" class="fa-solid fa-ticket text-blue-700 px-2 py-1"
                    title="Issue Tickets" aria-label="Issue Tickets"></a>`;
            }

            if (@js($status) === @js($ticketIssuedStatus)) {
                return printedFiles.length
                    ? statusButton(
                        sale.id,
                        @js($ticketPrintedStatus),
                        "Ticket Printed",
                        isTicketPrintBlocked(sale) ? "BFTN must be received and due amount must be cleared before printing" : "Sync group to its furthest status",
                        "verifyBtn",
                        isTicketPrintBlocked(sale),
                    )
                        + printedFileRows(sale, printedFiles)
                    : referenceBy(sale);
            }

            if (sale.status === @js($pendingStatus)) {
                return `<button class="bg-red-500 text-white px-2 py-1 rounded verifyBtn"
                    data-id="${sale.id}" data-status="{{ $paymentVerifiedStatus }}"
                    title="Sold by: ${escapeHtml(sale.sold_by)}">Verify Payment</button>`;
            }

            if (sale.status === @js($ticketIssuedStatus)) {
                return printedFiles.length
                    ? statusButton(
                        sale.id,
                        @js($ticketPrintedStatus),
                        "Ticket Printed",
                        isTicketPrintBlocked(sale) ? "BFTN must be received and due amount must be cleared before printing" : `Ticket Issued by: ${verifiedBy}`,
                        "verifyBtn",
                        isTicketPrintBlocked(sale),
                    )
                        + printedFileRows(sale, printedFiles)
                    : referenceBy(sale);
            }

            if (sale.status === @js($ticketPrintedStatus)) {
                if (sale.collect_from_office) {
                    return printedFiles.length
                        ? statusButton(
                            sale.id,
                            @js($collectFromOfficeStatus),
                            "Collected",
                            `ticket-printed by: ${verifiedBy}`,
                        )
                        : referenceBy(sale);
                }

                return printedFiles.length
                    ? statusButton(
                        sale.id,
                        @js($shipmentIdEnteredStatus),
                        "Add To Parcel",
                        `ticket-printed by: ${verifiedBy}`,
                        "shipmentIdEntryBtn",
                    )
                    : referenceBy(sale);
            }

            if (sale.status === @js($shipmentIdEnteredStatus)) {
                return printedFiles.length
                    ? statusButton(sale.id, @js($shippedStatus), "Shipped", `shipment_id_entered by: ${verifiedBy}`)
                    : referenceBy(sale);
            }

            return "";
        }

        function isBftnPending(sale) {
            return sale.bftn_status === "yes" && !sale.bftn_received;
        }

        function isTicketPrintBlocked(sale) {
            return isBftnPending(sale) || Number(sale.due_amount || 0) > 0;
        }

        function statusButton(id, statusValue, label, title, className = "verifyBtn", disabled = false) {
            const buttonClass = disabled
                ? "bg-gray-400 text-gray-700 cursor-not-allowed"
                : "bg-red-500 text-white";

            return `<button class="${buttonClass} px-2 py-1 rounded ${className}"
                data-id="${id}" data-status="${statusValue}" title="${title}"${disabled ? " disabled" : ""}>${label}</button>`;
        }

        function printedFileRows(sale, files) {
            const statusLabels = @js(config('sales.statuses'));
            const rows = files.map((file) => {
                const ownerStatus = file.sale?.status;
                const statusLabel = ownerStatus && ownerStatus !== @js($ticketIssuedStatus)
                    ? `<span class="text-xs text-gray-600">(${escapeHtml(statusLabels[ownerStatus] || ownerStatus)})</span>`
                    : "";

                return `
                    <div class="flex items-center gap-2">
                        <a href="/ship-ticket-sales/${file.sales_id}" target="_blank"
                           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-semibold">
                            Sale #${escapeHtml(file.sales_id)}
                        </a>
                        ${@js($status) === @js($ticketIssuedStatus) ? `
                            <a href="/tickets/open/${file.sales_id}/${encodeURIComponent(file.filename)}" target="_blank"
                               class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-semibold"
                               title="${escapeHtml(file.filename)}">
                                <i class="fas fa-file-pdf"></i> ${escapeHtml(file.filename)}
                            </a>
                            ${statusLabel}
                        ` : ""}
                    </div>`;
            });

            return `<div class="flex flex-col gap-2 mt-2">${rows.join("")}</div>`;
        }

        function referenceBy(sale) {
            const groupId = sale.printed_tickets?.[0]?.group_by_id;

            return groupId
                ? `<p class="text-sm font-semibold text-gray-700">Reference By ${escapeHtml(groupId)}</p>`
                : "";
        }

        let selectedBftnButton = null;

        function openBftnReceivedModal(button) {
            selectedBftnButton = button;
            const tentativeDate = button.dataset.tentativeDate || "Not specified";
            document.getElementById("bftnTentativeDate").textContent = tentativeDate;
            const receivedAt = document.getElementById("bftnReceivedAt");
            receivedAt.value = new Date().toISOString().slice(0, 16);
            document.getElementById("bftnReceivedModal").classList.remove("hidden");
            document.getElementById("bftnReceivedModal").classList.add("flex");
        }

        function closeBftnReceivedModal() {
            document.getElementById("bftnReceivedModal").classList.add("hidden");
            document.getElementById("bftnReceivedModal").classList.remove("flex");
            selectedBftnButton = null;
        }

        async function markBftnReceived(button, getList) {
            openBftnReceivedModal(button);
            const form = document.getElementById("bftnReceivedForm");
            form.onsubmit = async (event) => {
                event.preventDefault();

                const response = await fetch(`/sale/bftn-received/${selectedBftnButton.dataset.id}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                },
                body: JSON.stringify({ received_at: document.getElementById("bftnReceivedAt").value }),
            });
            const result = await response.json();
                closeBftnReceivedModal();
                await Swal.fire({ title: result.success ? "Updated" : "Error", text: result.message, icon: result.success ? "success" : "error" });
                if (result.success) getList();
            };
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
                } else if (button.classList.contains("bftnReceivedBtn")) {
                    markBftnReceived(button, window.getList);
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
        $status == $ticketPrintedStatus ? 'Address' : null,
        $status == $paymentVerifiedStatus ? 'Company' : null,
        $status == $shipmentIdEnteredStatus ? 'Shipment Id' : null,
        $status == $pendingStatus ? 'Total Ticket Value' : null,
        $status == $pendingStatus ? 'Other Fee' : null,
        $status == $pendingStatus ? 'Discount Amount' : null,
        $status == $pendingStatus ? 'Received Amount' : null,
        $status == $pendingStatus ? 'Transaction ID' : null,
        $status == $pendingStatus ? 'Payment Methods' : null,
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

    <div id="bftnReceivedModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h3 class="text-lg font-bold text-gray-800">Confirm BFTN Received</h3>
            <p class="mt-2 text-sm text-gray-600">Tentative Date: <span id="bftnTentativeDate" class="font-semibold"></span></p>
            <form id="bftnReceivedForm" class="mt-4 space-y-4">
                <div>
                    <label for="bftnReceivedAt" class="block text-sm font-semibold text-gray-700">Received At</label>
                    <input id="bftnReceivedAt" type="datetime-local" required
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded bg-gray-200 px-4 py-2 text-gray-800" onclick="closeBftnReceivedModal()">Cancel</button>
                    <button type="submit" class="rounded bg-green-600 px-4 py-2 text-white">Save Received Date</button>
                </div>
            </form>
        </div>
    </div>
</div>
