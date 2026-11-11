import { initializeDataTable } from '../services/data-table.js';
import { apiRequest, refreshDataTable } from '../services/api.js';
import { renderRefundCustomer } from '../utils/refund-customer.js';

const refundRequestPage = document.getElementById('refundRequestPage');
const refundStatus = refundRequestPage?.dataset.status || '';
const isRequested = refundStatus === 'requested';
const isPartnerApproved = refundStatus === 'partner_approved';
const isPaymentDetailsAdded = refundStatus === 'payment_details_added';
const isCompleted = refundStatus === 'completed';
const routeTemplate = (name) => refundRequestPage.dataset[name];
const formatCustomerRefund = (amount, type, row) => {
    const refundAmount = Number(amount || 0);
    if (type !== 'display') return refundAmount;
    if (refundAmount < 0 || row.refund_calculation_valid === false) {
        return '<span class="font-semibold text-amber-700" title="Refund calculation needs correction">Review</span>';
    }

    return refundAmount.toFixed(2);
};

const completedColumns = [
    { data: 'sale.id', render: (data) => data || 'N/A' },
    { data: 'sale.customer_name', render: (data, type, row) => renderRefundCustomer(data, type, row.sale) },
    { data: null, render: (data, type, row) => escapeHtml(row.sale?.ships?.name || 'N/A') },
    { data: 'sale.journey_date', render: formatRequestedDate },
    { data: 'sale.ticket_count' },
    { data: 'total_refund_tickets' },
    { data: 'gross_refund_amount' },
    { data: 'customer_refund_amount', render: formatCustomerRefund },
    { data: 'partner_share_amount' },
    { data: 'company_retained_amount' },
    { data: 'due_adjusted_amount', render: (data) => Number(data || 0).toFixed(2) },
    { data: 'customer_refund_after_due_adjustment', render: (data) => `<div class="bg-red-100 px-2 py-1 font-semibold text-red-700">${Number(data || 0).toFixed(2)}</div>` },
    {
        data: 'settlement_label',
        render: (data, type, row) => {
            if (type !== 'display') return data;
            const references = (row.adjustment_references || []).map(reference =>
                `<div class="mt-1 text-xs text-blue-700">Sale #${Number(reference.sale_id)}: ${Number(reference.amount).toFixed(2)}</div>`
            ).join('');
            return `<span class="rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-800">${escapeHtml(data || 'Refunded')}</span><div class="mt-1 text-xs text-gray-600">Paid: ${Number(row.customer_refund_paid || 0).toFixed(2)} · Adjusted: ${Number(row.refund_adjusted_amount || 0).toFixed(2)}</div>${references}`;
        },
    },
    { data: 'status', render: renderRefundStatus },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `<a href="/refunded/${row.sale?.id}/details" class="rounded bg-blue-700 px-2 py-1 text-white" title="View refund details">Details</a>`,
    },
];

const columns = isCompleted ? completedColumns : [
    { data: 'id' },
    { data: 'sale.id', render: (data) => data || 'N/A' },
    { data: 'sale.customer_name', render: (data, type, row) => renderRefundCustomer(data, type, row.sale) },
    { data: 'sale.journey_date', render: formatRequestedDate },
    { data: 'refund_type' },
    { data: 'total_purchase_tickets' },
    { data: 'total_refund_tickets' },
    { data: 'gross_refund_amount' },
    { data: 'customer_refund_amount', render: formatCustomerRefund },
    { data: 'partner_share_amount' },
    { data: 'company_retained_amount' },
    { data: 'due_adjusted_amount', render: (data) => Number(data || 0).toFixed(2) },
    {
        data: 'customer_refund_after_due_adjustment',
        render: (data, type, row) => `<div class="bg-red-100 px-2 py-1 font-semibold text-red-700">${Number(data || 0).toFixed(2)}</div>${Number(row.refund_adjusted_amount || 0) > 0 ? `<div class="text-xs text-blue-700">Adjusted to new sales: ${Number(row.refund_adjusted_amount).toFixed(2)}</div>` : ''}`,
    },
    isPaymentDetailsAdded ? { data: 'refund_payment_details', render: (data) => escapeHtml(data || 'N/A') } : null,
    { data: 'status', render: renderRefundStatus },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => {
            if (type !== 'display') return '';
            if (isCompleted) {
                return `<a data-permission="refunds.view" href="/refunded/${row.sale?.id}/details" class="rounded bg-blue-700 px-2 py-1 text-white" title="View refund details">Details</a>`;
            }

            return `<div class="flex items-center gap-2 pb-2">
                            <button data-permission="refunds.edit" class="fas fa-edit text-blue-950 px-2 py-1 rounded requestedEditBtn"
                                data-id="${row.sale?.id ?? ''}"
                                data-request-id="${row.id}"
                                data-refund-type="${escapeHtml(row.refund_type || 'partial')}"
                                data-received_total_amount="${row.sale.ticket_value}"
                                data-due-amount="${row.sale?.due_amount ?? 0}"
                                data-discount-amount="${row.sale?.discount_amount ?? 0}"
                                data-other-fee="${row.sale?.other_fee ?? 0}"
                                data-extra-received="${row.sale?.extra_received_amount ?? 0}"
                                data-extra-remaining="${row.sale?.extra_remaining_amount ?? 0}"
                                data-categories="${encodeURIComponent(JSON.stringify(row.edit_categories || []))}"
                                data-gross-amount="${row.gross_refund_amount}"
                                data-ticket-count="${row.refunded_number_of_tickets}"
                                data-customer-charge="${row.customer_charge_percent ?? 0}"
                                data-partner-share="${row.partner_share_percent ?? 0}"
                                data-company-error="${encodeURIComponent(JSON.stringify(row.company_error_refund || null))}"
                                data-remark="${encodeURIComponent(row.remark || '')}"
                                title="Edit request"></button>
                            <button data-permission="refunds.cancel" class="bg-yellow-600 text-white px-2 py-1 rounded cancelRefundBtn"
                                data-id="${row.id}" title="Cancel refund request">Cancel</button>
                            ${isRequested ? `<button data-permission="refunds.approve" class="bg-green-700 text-white px-2 py-1 rounded approveRefundBtn"
                                data-id="${row.id}" title="Approve refund request">Approve</button>` : ''}
                            ${isPartnerApproved || isPaymentDetailsAdded ? `<button data-permission="refunds.payment_details" class="bg-blue-700 text-white px-2 py-1 rounded addPaymentDetailsBtn"
                                data-id="${row.id}" data-details="${escapeHtml(row.refund_payment_details || '')}" title="${isPaymentDetailsAdded ? 'Edit' : 'Add'} refund payment details">${isPaymentDetailsAdded ? 'Edit' : 'Add'} Payment Details</button>` : ''}
                            ${isPaymentDetailsAdded ? `<button data-permission="refunds.customer_payment" class="bg-green-700 text-white px-2 py-1 rounded refundCustomerBtn"
                                data-id="${row.id}" title="Refund customer">Refund</button>` : ''}
                            </div>`;
        },
    },
].filter(Boolean);

const filters = () => ({
    ship_id: document.getElementById('requestedShip').value,
    company_id: document.getElementById('requestedCompany').value,
    journey_date: document.getElementById('requestedJourneyDate').value,
    search: new URLSearchParams(window.location.search).get('search')
        ? { value: new URLSearchParams(window.location.search).get('search') }
        : undefined,
});
initializeDataTable({ table: document.getElementById('requestedRefundsTable'), columns, filters });

function formatRequestedDate(value) {
    return value ? new Date(value).toLocaleDateString() : 'N/A';
}

async function approveRefund(button) {
    const confirmation = await Swal.fire({
        title: 'Approve refund request?',
        text: 'This request will move to the partner-approved stage.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, approve',
        cancelButtonText: 'Cancel',
    });

    if (!confirmation.isConfirmed) return;

    const response = await fetch(routeTemplate('approveUrl').replace('__ID__', button.dataset.id), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
    });
    const result = await response.json();
    await Swal.fire({
        title: result.success ? 'Approved' : 'Error',
        text: result.message || 'Refund approval failed.',
        icon: result.success ? 'success' : 'error',
    });
    if (result.success) refreshDataTable('requestedRefundsTable');
}

async function cancelRefund(button) {
    const confirmation = await Swal.fire({
        title: 'Cancel refund request?',
        text: 'This request will be removed from the requested list.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel request',
        cancelButtonText: 'Keep request',
    });

    if (!confirmation.isConfirmed) return;

    const response = await fetch(routeTemplate('cancelUrl').replace('__ID__', button.dataset.id), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
    });
    const result = await response.json();
    await Swal.fire({
        title: result.success ? 'Cancelled' : 'Error',
        text: result.message || 'Could not cancel refund request.',
        icon: result.success ? 'success' : 'error',
    });
    if (result.success) refreshDataTable('requestedRefundsTable');
}

function addPaymentDetails(button) {
    const modal = document.getElementById('refundPaymentDetailsModal');
    modal.dataset.refundId = button.dataset.id;
    modal.querySelector('h3').textContent = isPaymentDetailsAdded ? 'Edit Refund Payment Details' : 'Add Refund Payment Details';
    document.getElementById('refundPaymentDetailsInput').value = button.dataset.details || '';
    document.getElementById('refundPaymentDetailsError').hidden = true;
    modal._openModal();
}

async function savePaymentDetails(event) {
    event.preventDefault();
    const modal = document.getElementById('refundPaymentDetailsModal');
    const input = document.getElementById('refundPaymentDetailsInput');
    const error = document.getElementById('refundPaymentDetailsError');
    const submit = modal.querySelector('button[type="submit"]');
    if (modal.dataset.saving === '1') return;
    if (!input.value.trim()) {
        error.textContent = 'Payment details are required.';
        error.hidden = false;
        modal._resetSubmitLoading();
        return;
    }
    submit.disabled = true;
    modal.dataset.saving = '1';
    error.hidden = true;
    try {
        const response = await fetch(routeTemplate('paymentDetailsUrl').replace('__ID__', modal.dataset.refundId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ refund_payment_details: input.value.trim() }),
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.errors?.refund_payment_details?.[0] || result.errors?.status?.[0] || result.message || 'Could not save payment details.');
        }
        modal._closeModal();
        refreshDataTable('requestedRefundsTable');
    } catch (exception) {
        error.textContent = exception.message || 'Could not save payment details. Please try again.';
        error.hidden = false;
    } finally {
        modal.dataset.saving = '0';
        modal._resetSubmitLoading();
    }
}
async function refundCustomer(button) {
    const confirmation = await Swal.fire({
        title: 'Complete customer refund?',
        text: 'This refund will be marked as completed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, refund',
        cancelButtonText: 'Cancel',
    });

    if (!confirmation.isConfirmed) return;

    const response = await fetch(routeTemplate('customerPaymentUrl').replace('__ID__', button.dataset.id), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
    });
    const responseData = await response.json();

    await Swal.fire({
        title: responseData.success ? 'Refunded' : 'Error',
        text: responseData.message || 'Could not complete the refund.',
        icon: responseData.success ? 'success' : 'error',
    });

    if (responseData.success) refreshDataTable('requestedRefundsTable');
}

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('refundPaymentDetailsForm')?.addEventListener('submit', savePaymentDetails);
    ['requestedShip', 'requestedCompany', 'requestedJourneyDate'].forEach((id) => {
        document.getElementById(id)?.addEventListener('change', () => refreshDataTable('requestedRefundsTable'));
    });
    document.getElementById('requestedRefundsTable')?.addEventListener('click', (event) => {
        const button = event.target.closest('.requestedEditBtn');
        if (button) {
            document.dispatchEvent(new CustomEvent('refund:open', { detail: { button, editing: true } }));
            return;
        }

        const approveRefundButton = event.target.closest('.approveRefundBtn');
        if (approveRefundButton) approveRefund(approveRefundButton);

        const cancelRefundButton = event.target.closest('.cancelRefundBtn');
        if (cancelRefundButton) cancelRefund(cancelRefundButton);

        const paymentDetailsButton = event.target.closest('.addPaymentDetailsBtn');
        if (paymentDetailsButton) addPaymentDetails(paymentDetailsButton);

        const refundCustomerButton = event.target.closest('.refundCustomerBtn');
        if (refundCustomerButton) refundCustomer(refundCustomerButton);
    });
});

import { escapeHtml } from '../utils/escape-html';

function renderRefundStatus(value, type, row) {
    if (type !== 'display') return value;
    const loss = row.company_error_refund;
    return `${escapeHtml(value || '')}${loss ? `<div class="mt-1 rounded bg-red-100 px-2 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-200">Company Error: ${Number(loss.loss_amount).toFixed(2)}</div>` : ''}`;
}
