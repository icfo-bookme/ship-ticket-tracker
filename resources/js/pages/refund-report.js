import { initializeDataTable } from '../services/data-table.js';
import { refreshDataTable } from '../services/api.js';
import { escapeHtml } from '../utils/escape-html.js';

const table = document.getElementById('refundReportTable');
if (table) {
    const filterIds = [
        'refundReportShip',
        'refundReportCompany',
        'refundReportStatus',
        'refundReportType',
    ];
    const filters = () => ({
        ship_id: document.getElementById('refundReportShip').value,
        company_id: document.getElementById('refundReportCompany').value,
        status: document.getElementById('refundReportStatus').value,
        refund_type: document.getElementById('refundReportType').value,
    });
    const columns = [
        { data: 'id' },
        { data: 'sales_id' },
        { data: null, render: (data, type, row) => type === 'display' ? escapeHtml(row.customer_name) : row.customer_name },
        { data: 'ship_name', render: (data) => escapeHtml(data || 'N/A') },
        { data: 'requested_at', render: formatDateTime },
        { data: 'refund_type' },
        { data: 'refunded_number_of_tickets' },
        { data: 'gross_refund_amount', render: formatMoney },
        { data: 'refund_discount_amount', render: formatMoney },
        { data: 'other_fee_deduction', render: formatMoney },
        { data: 'customer_charge_amount', render: formatMoney },
        { data: 'partner_share_amount', render: formatMoney },
        { data: 'company_retained_amount', render: formatMoney },
        { data: 'due_adjusted_amount', render: formatMoney },
        { data: 'final_customer_refund', render: formatMoney },
        { data: 'customer_refund_paid', render: formatMoney },
        { data: 'status', render: (data) => escapeHtml(String(data || 'N/A').replaceAll('_', ' ')) },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: (data, type, row) => type !== 'display' ? '' :
                `<button type="button" class="refund-report-detail-trigger rounded border border-blue-200 px-2 py-1 text-sm text-blue-700 hover:bg-blue-50" data-refund-id="${Number(row.id)}">Details</button>`,
        },
    ];

    initializeDataTable({
        table,
        columns,
        filters,
        dataSrc: (response) => {
            updateSummary(response.totals || {});
            return response.data || [];
        },
    });

    filterIds.forEach((id) => document.getElementById(id)?.addEventListener('change', () => refreshDataTable(table.id)));
    document.getElementById('clearRefundReportFilters')?.addEventListener('click', () => {
        filterIds.forEach((id) => {
            const element = document.getElementById(id);
            if (element) element.value = id === 'refundReportStatus' || id === 'refundReportType' ? 'all' : '';
        });
        refreshDataTable(table.id);
    });

    table.addEventListener('click', (event) => {
        const trigger = event.target.closest('.refund-report-detail-trigger');
        if (!trigger || !window.jQuery || !$.fn.DataTable.isDataTable(table)) return;
        const refund = $(table).DataTable().row(trigger.closest('tr')).data();
        if (refund) showRefundDetails(refund);
    });
}

function formatMoney(value) {
    const amount = Number(String(value ?? 0).replaceAll(',', ''));
    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function formatDateTime(value) {
    if (!value) return 'N/A';
    const date = new Date(value.replace(' ', 'T'));
    return Number.isNaN(date.getTime()) ? escapeHtml(value) : escapeHtml(date.toLocaleString());
}

function updateSummary(totals) {
    const ids = {
        request_count: 'refundReportCount',
        open_count: 'refundReportOpen',
        completed_count: 'refundReportCompleted',
        gross_amount: 'refundReportGross',
        discount_amount: 'refundReportDiscount',
        other_fee_deduction: 'refundReportOtherFee',
        due_adjusted_amount: 'refundReportDueAdjusted',
        customer_refund_paid: 'refundReportPaid',
        extra_payment_refund_paid: 'refundReportExtraPaid',
    };
    Object.entries(ids).forEach(([key, id]) => {
        const element = document.getElementById(id);
        if (element) element.textContent = key.endsWith('_count') ? String(totals[key] ?? 0) : formatMoney(totals[key]);
    });
}

function showRefundDetails(refund) {
    const modal = document.getElementById('refundReportDetailModal');
    const content = document.getElementById('refundReportDetails');
    modal.querySelector('h3').textContent = `Refund Request #${refund.id}`;

    const fields = [
        ['Sale ID', refund.sales_id],
        ['Customer', refund.customer_name],
        ['Mobile', refund.customer_mobile],
        ['Company', refund.company_name],
        ['Ship', refund.ship_name],
        ['Journey Date', refund.journey_date],
        ['Requested At', refund.requested_at],
        ['Refund Type / Status', `${refund.refund_type} / ${refund.status}`],
        ['Gross Refund', formatMoney(refund.gross_refund_amount)],
        ['Refund Discount', formatMoney(refund.refund_discount_amount)],
        ['Other Fee Deducted', formatMoney(refund.other_fee_deduction)],
        ['Customer Charge', formatMoney(refund.customer_charge_amount)],
        ['Partner Share', formatMoney(refund.partner_share_amount)],
        ['Company Retained', formatMoney(refund.company_retained_amount)],
        ['Due Adjusted', formatMoney(refund.due_adjusted_amount)],
        ['Final Customer Refund', formatMoney(refund.final_customer_refund)],
        ['Customer Refund Paid', formatMoney(refund.customer_refund_paid)],
        ['Partner Payment Details', refund.refund_payment_details || 'N/A'],
        ['Remark', refund.remark || 'N/A'],
    ];
    const fieldMarkup = fields.map(([label, value]) => `
        <div class="border-b border-gray-100 pb-2 dark:border-gray-600">
            <dt class="text-xs font-medium uppercase text-gray-500">${escapeHtml(label)}</dt>
            <dd class="mt-1 break-words text-sm font-medium text-gray-900 dark:text-gray-100">${escapeHtml(value ?? 'N/A')}</dd>
        </div>`).join('');
    const ticketMarkup = (refund.tickets || []).map((ticket) => `
        <tr class="border-b border-gray-200 dark:border-gray-600">
            <td class="px-3 py-2">${escapeHtml(ticket.category_name || 'Ticket')}</td>
            <td class="px-3 py-2">${escapeHtml(ticket.category_type || 'N/A')}</td>
            <td class="px-3 py-2 text-right">${Number(ticket.refunded_quantity || 0)} / ${Number(ticket.purchased_quantity || 0)}</td>
            <td class="px-3 py-2 text-right">${formatMoney(ticket.unit_amount)}</td>
            <td class="px-3 py-2 text-right">${formatMoney(ticket.gross_amount)}</td>
        </tr>`).join('');
    const paymentMarkup = (refund.customer_payments || []).map((payment) => `
        <div class="rounded-md border border-gray-200 p-3 dark:border-gray-600">
            <div class="flex justify-between gap-3"><strong>${escapeHtml(payment.payment_method || 'Method N/A')}</strong><strong>${formatMoney(payment.amount)}</strong></div>
            <p class="mt-1 text-xs text-gray-500">${escapeHtml(payment.status || '')} · ${escapeHtml(payment.paid_at || 'Date N/A')} · ${escapeHtml(payment.transaction_id || 'No transaction ID')}</p>
        </div>`).join('');

    content.innerHTML = `
        <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2 lg:grid-cols-3">${fieldMarkup}</dl>
        <section>
            <h4 class="mb-2 text-sm font-semibold">Returned ticket categories</h4>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="px-3 py-2">Category</th><th class="px-3 py-2">Direction</th><th class="px-3 py-2 text-right">Returned / Purchased</th><th class="px-3 py-2 text-right">Unit</th><th class="px-3 py-2 text-right">Gross</th></tr></thead><tbody>${ticketMarkup || '<tr><td class="px-3 py-3 text-gray-500" colspan="5">No ticket-category detail recorded.</td></tr>'}</tbody></table></div>
        </section>
        <section><h4 class="mb-2 text-sm font-semibold">Customer refund payments</h4><div class="grid gap-2 sm:grid-cols-2">${paymentMarkup || '<p class="text-sm text-gray-500">No customer payment recorded.</p>'}</div></section>`;
    modal._openModal();
}
