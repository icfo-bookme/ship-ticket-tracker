import { initializeDataTable } from '../services/data-table.js';
const configElement = document.getElementById('saleStatusConfig');
const config = {
    status: configElement?.dataset.status || '',
    ticketPrintedStatus: configElement?.dataset.ticketPrintedStatus || '',
    paymentVerifiedStatus: configElement?.dataset.paymentVerifiedStatus || '',
    shipmentIdEnteredStatus: configElement?.dataset.shipmentIdEnteredStatus || '',
    pendingStatus: configElement?.dataset.pendingStatus || '',
    collectFromOfficeStatus: configElement?.dataset.collectFromOfficeStatus || '',
    ticketIssuedStatus: configElement?.dataset.ticketIssuedStatus || '',
    shippedStatus: configElement?.dataset.shippedStatus || '',
    statusLabels: JSON.parse(configElement?.dataset.statusLabels || '{}'),
    ticketIssueUrl: configElement?.dataset.ticketIssueUrl || '',
    canCollectDue: configElement?.dataset.canCollectDue === 'true',
    destroyUrl: configElement?.dataset.destroyUrl || '',
    verifyUrl: configElement?.dataset.verifyUrl || '',
    bftnReceivedUrl: configElement?.dataset.bftnReceivedUrl || '',
};

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

const shipFilter = document.getElementById("shipFilter");
const companyFilter = document.getElementById("companyFilter");
const journeyDateFilter = document.getElementById("journeyDateFilter");
const clearFiltersBtn = document.getElementById("clearFilters");

const salesTable = document.getElementById('salesTable');
const refreshSalesTable = () => document.dispatchEvent(new CustomEvent('data-table:refresh', {
    detail: { tableId: 'salesTable' },
}));

async function deleteSale(button, refresh) {
    const confirmation = await Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
    });

    if (!confirmation.isConfirmed) return;

    try {
        const response = await fetch(config.destroyUrl.replace('__ID__', button.dataset.id), {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to delete sale.');
        }

        await Swal.fire({ title: 'Deleted!', text: 'Sale has been successfully deleted.', icon: 'success' });
        refresh();
    } catch (error) {
        await Swal.fire({ title: 'Error', text: error.message || 'Unable to delete the sale.', icon: 'error' });
    }
}

const columns = [
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
        data: "whatsapp_display",
        render: copyCell,
    },
    {
        data: null,
        render: (row) => escapeHtml(row.ship?.name || row.ships?.name || "Not available"),
    },
    ...(config.status === config.ticketPrintedStatus ? [
        {
            data: null,
            title: "Address",
            render: (row) => row.collect_from_office
                ? "Collect from Office"
                : escapeHtml(row.address || "Not available"),
        },
    ] : []),
    ...(config.status === config.paymentVerifiedStatus ? [
        {
            data: null,
            title: "Company",
            render: (row) => escapeHtml(row.company?.name || row.companies?.name || "Not available"),
        },
    ] : []),
    ...(config.status === config.shipmentIdEnteredStatus ? [
        {
            data: "shipment.shipment_id",
            render: (data) => escapeHtml(data ?? "Not available"),
        },
    ] : []),
    ...(config.status === config.pendingStatus ? [
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
            data: "total_payable",
            title: "Total Payable",
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
    ] : []),
    {
        data: null,
        orderable: false,
            render: (data, type, row) => createActionButtons(row),
    },
];

const createdRow = (row, sale) => {
    if (sale.bftn_status === 'yes' && !sale.bftn_received) {
        row.classList.add('bg-amber-300');
        row.querySelectorAll('td').forEach((cell) => {
            cell.classList.add('!bg-amber-300');
        });
    }
};

const filters = () => ({
    ship_id: shipFilter.value,
    company_id: companyFilter.value,
    journey_date: journeyDateFilter.value,
});
initializeDataTable({ table: salesTable, columns, createdRow, filters });

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

    const items = payments.filter(Boolean).map((payment) => {
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
    const proofs = (payments || []).filter((payment) => payment?.payment_proof);

    if (!proofs.length) {
        return '<span class="text-gray-400 text-sm">N/A</span>';
    }

    const thumbs = proofs.map((payment) => `
                <button type="button" data-permission="payments.proof.view" class="paymentProofBtn" data-proof-url="/payments/${payment.id}/proof"
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
        .filter(Boolean)
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
    let dueButton = "";
    let editButton = "";
    let deleteButton = "";
    let bftnReceivedButton = "";

    if (config.canCollectDue) {
        dueButton = Number(sale.due_amount) > 0
            ? `<button data-permission="payments.due.collect" class="bg-yellow-500 text-black px-2 py-1 rounded dueBtn"
                        data-id="${sale.id}"
                        data-due_amount="${sale.due_amount}"
                        title="Due Amount: ${escapeHtml(sale.due_amount)}">Due</button>`
            : "";
    }

    {
        editButton = `<a data-permission="sales.edit" href="/ship-ticket-sales/${sale.id}">
                        <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
                    </a>`;
    }

    {
        deleteButton = `<button data-permission="sales.delete" class="fas fa-trash text-red-500 bg-white px-2 py-1 border border-gray-300 rounded deleteBtn"
                        data-id="${sale.id}" title="Delete"></button>`;
    }

    {
        bftnReceivedButton = sale.bftn_status === 'yes' && !sale.bftn_received
            ? `<button data-permission="sales.bftn.receive" class="bg-green-600 text-white px-2 py-1 rounded bftnReceivedBtn"
                        data-id="${sale.id}" data-tentative-date="${escapeHtml(sale.bftn?.bftn_date_time || 'Not specified')}" title="Mark BFTN as received">BFTN Received</button>`
            : "";
    }

    const activeRefund = (sale.refunds || []).find((refund) => !['cancelled', 'completed'].includes(refund.status));
    const editRefundButton = activeRefund
        ? `<a data-permission="refunds.view" href="/refund-requests?search=${encodeURIComponent(sale.id)}"
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
    if (sale.status === config.collectFromOfficeStatus) {
        return sale.grouped_tickets?.length
            ? '<span class="text-green-700 font-semibold">Collected</span>'
            : referenceBy(sale);
    }

    const verifiedBy = escapeHtml(sale.verifyby?.[0]?.verified_by_user?.name || "Unknown");
    const printedFiles = sale.grouped_tickets || [];

    if (sale.status === config.paymentVerifiedStatus) {
        const issueTicketUrl = config.ticketIssueUrl.replace('__SALE_ID__', encodeURIComponent(sale.id));

        return `<a data-permission="sales.issue" href="${issueTicketUrl}" class="fa-solid fa-ticket text-blue-700 px-2 py-1"
                    title="Issue Tickets" aria-label="Issue Tickets"></a>`;
    }

    if (config.status === config.ticketIssuedStatus) {
        return printedFiles.length
            ? statusButton(
                sale.id,
                config.ticketPrintedStatus,
                "Ticket Printed",
                isTicketPrintBlocked(sale) ? "BFTN must be received and due amount must be cleared before printing" : "Sync group to its furthest status",
                "verifyBtn",
                isTicketPrintBlocked(sale),
            )
            + printedFileRows(sale, printedFiles)
            : referenceBy(sale);
    }

    if (sale.status === config.pendingStatus) {
        return `<button data-permission="sales.verify" class="bg-red-500 text-white px-2 py-1 rounded verifyBtn"
                    data-id="${sale.id}" data-status="${config.paymentVerifiedStatus}"
                    title="Sold by: ${escapeHtml(sale.sold_by)}">Verify Payment</button>`;
    }

    if (sale.status === config.ticketIssuedStatus) {
        return printedFiles.length
            ? statusButton(
                sale.id,
                config.ticketPrintedStatus,
                "Ticket Printed",
                isTicketPrintBlocked(sale) ? "BFTN must be received and due amount must be cleared before printing" : `Ticket Issued by: ${verifiedBy}`,
                "verifyBtn",
                isTicketPrintBlocked(sale),
            )
            + printedFileRows(sale, printedFiles)
            : referenceBy(sale);
    }

    if (sale.status === config.ticketPrintedStatus) {
        if (sale.collect_from_office) {
            return printedFiles.length
                ? statusButton(
                    sale.id,
                    config.collectFromOfficeStatus,
                    "Collected",
                    `ticket-printed by: ${verifiedBy}`,
                )
                : referenceBy(sale);
        }

        return printedFiles.length
            ? statusButton(
                sale.id,
                config.shipmentIdEnteredStatus,
                "Add To Parcel",
                `ticket-printed by: ${verifiedBy}`,
                "shipmentIdEntryBtn",
            )
            : referenceBy(sale);
    }

    if (sale.status === config.shipmentIdEnteredStatus) {
        return printedFiles.length
            ? statusButton(sale.id, config.shippedStatus, "Shipped", `shipment_id_entered by: ${verifiedBy}`)
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

async function varifyShipment(button, refresh) {
    const confirmation = await Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, verify it!',
        cancelButtonText: 'Cancel',
    });

    if (!confirmation.isConfirmed) return;

    try {
        const response = await fetch(config.verifyUrl
            .replace('__ID__', button.dataset.id)
            .replace('__STATUS__', button.dataset.status), {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to verify shipment.');
        }

        await Swal.fire({ title: 'Verified!', text: 'Shipment has been successfully verified.', icon: 'success' });
        refresh();
    } catch (error) {
        await Swal.fire({ title: 'Error', text: error.message || 'Unable to verify shipment.', icon: 'error' });
    }
}

function statusButton(id, statusValue, label, title, className = "verifyBtn", disabled = false) {
    const permission = {
        [config.paymentVerifiedStatus]: 'sales.verify',
        [config.ticketIssuedStatus]: 'sales.issue',
        [config.ticketPrintedStatus]: 'sales.mark_printed',
        [config.shipmentIdEnteredStatus]: 'sales.create_parcel',
        [config.shippedStatus]: 'sales.mark_shipped',
        [config.collectFromOfficeStatus]: 'sales.mark_collected',
    }[statusValue];
    const buttonClass = disabled
        ? "bg-gray-400 text-gray-700 cursor-not-allowed"
        : "bg-red-500 text-white";

    return `<button data-permission="${permission}" class="${buttonClass} px-2 py-1 rounded ${className}"
                data-id="${id}" data-status="${statusValue}" title="${title}"${disabled ? " disabled" : ""}>${label}</button>`;
}

function printedFileRows(sale, files) {
    const statusLabels = config.statusLabels;
    const rows = files.map((file) => {
        const ownerStatus = file.sale?.status;
        const statusLabel = ownerStatus && ownerStatus !== config.ticketIssuedStatus
            ? `<span class="text-xs text-gray-600">(${escapeHtml(statusLabels[ownerStatus] || ownerStatus)})</span>`
            : "";

        return `
                    <div class="flex items-center gap-2">
                        <a data-permission="sales.edit" href="/ship-ticket-sales/${file.sales_id}" target="_blank"
                           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-semibold">
                            Sale #${escapeHtml(file.sales_id)}
                        </a>
                        ${config.status === config.ticketIssuedStatus ? `
                            <a data-permission="sales.print" href="/tickets/open/${file.sales_id}/${encodeURIComponent(file.filename)}" target="_blank"
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
    document.getElementById("bftnReceivedModal")._openModal?.();
}

function closeBftnReceivedModal() {
    document.getElementById("bftnReceivedModal")._closeModal?.();
    selectedBftnButton = null;
}

async function markBftnReceived(button, getList) {
    openBftnReceivedModal(button);
    const form = document.getElementById("bftnReceivedForm");
    form.onsubmit = async (event) => {
        event.preventDefault();

        const response = await fetch(config.bftnReceivedUrl.replace('__ID__', selectedBftnButton.dataset.id), {
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
        if (result.success) refreshSalesTable();
    };
}

function bindSalesTableEvents() {
    const table = document.getElementById("salesTable");

    [shipFilter, companyFilter, journeyDateFilter].forEach((filter) => {
        filter?.addEventListener("change", () => refreshSalesTable());
    });

    clearFiltersBtn?.addEventListener("click", () => {
        [shipFilter, companyFilter, journeyDateFilter].forEach((filter) => {
            if (filter) filter.value = "";
        });
        refreshSalesTable();
    });

    table?.addEventListener("click", (event) => {
        const button = event.target.closest("button");
        if (!button) return;

        if (button.classList.contains("copyBtn")) {
            event.stopPropagation();
            copyToClipboard(button.dataset.copy);
        } else if (button.classList.contains("verifyBtn")) {
            document.dispatchEvent(new CustomEvent('sale:verify', {
                detail: {
                    button,
                    onSuccess: refreshSalesTable,
                },
            }));
        } else if (button.classList.contains("deleteBtn")) {
            deleteSale(button, refreshSalesTable);
        } else if (button.classList.contains("bftnReceivedBtn")) {
            markBftnReceived(button, refreshSalesTable);
        } else if (button.classList.contains("shipmentIdEntryBtn")) {
            varifyShipment(button, refreshSalesTable);
        } else if (button.classList.contains("dueBtn")) {
            document.dispatchEvent(new CustomEvent('due:open', {
                detail: { button, refresh: refreshSalesTable },
            }));
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
