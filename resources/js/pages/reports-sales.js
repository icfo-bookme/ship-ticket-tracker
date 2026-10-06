import { initializeDataTable } from '../services/data-table.js';
import { refreshDataTable } from '../services/api';
import { escapeHtml } from '../utils/escape-html';

const configElement = document.getElementById('salesReportConfig');
const config = { canEdit: configElement?.dataset.canEdit === 'true' };
const shipFilter = document.getElementById("shipFilter");
const companyFilter = document.getElementById("companyFilter");
const returnDateFilter = document.getElementById("returnDateFilter");
const clearFiltersBtn = document.getElementById("clearFilters");
const toggleAdvancedFiltersBtn = document.getElementById("toggleAdvancedFilters");
const advancedFilters = document.getElementById("advancedReportFilters");
const paymentMethodFilter = document.getElementById("payment_method");
const bftnFilter = document.getElementById("bftnFilter");
const startDateFilter = document.getElementById("startDate");
const endDateFilter = document.getElementById("endDate");
const startCreateDateFilter = document.getElementById("startCreateDate");
const endCreateDateFilter = document.getElementById("endCreateDate");
const paymentMethodBreakdownCards = document.getElementById('paymentMethodBreakdownCards');
const paymentMethodBreakdownEmpty = document.getElementById('paymentMethodBreakdownEmpty');
function totalElements() {
    return {
        total_number_of_tickets: document.getElementById("totalSellTickets"),
        total_ticket_fee: document.getElementById("totalSoldTicketsAmount"),
        total_other_fee: document.getElementById("totalOtherFees"),
        total_discount_amount: document.getElementById("totalDiscountAmount"),
        total_payable: document.getElementById("totalSold"),
        total_refunded_tickets: document.getElementById("totalRefundedTickets"),
        total_received_amount: document.getElementById("totalReceivedAmount"),
        total_due_amount: document.getElementById("totalDueAmount"),
        total_gross_refund_amount: document.getElementById("totalGrossRefundAmount"),
        total_other_fee_deduction: document.getElementById("totalOtherFeeDeduction"),
        total_refund_discount_amount: document.getElementById("totalRefundDiscountAmount"),
        total_customer_refund_paid: document.getElementById("totalCustomerRefundPaid"),
        total_due_adjusted_amount: document.getElementById("totalDueAdjustedAmount"),
        total_partner_share_amount: document.getElementById("totalPartnerShareAmount"),
        total_company_retained_amount: document.getElementById("totalCompanyRetainedAmount"),
        total_extra_refunded_amount: document.getElementById("totalExtraRefundedAmount"),
        total_bftn: document.getElementById("totalBftn"),
        total_bftn_pending: document.getElementById("totalBftnPending"),
        total_bftn_received: document.getElementById("totalBftnReceived"),
        total_bftn_amount: document.getElementById("totalBftnAmount"),
        total_bftn_pending_amount: document.getElementById("totalBftnPendingAmount"),
        total_bftn_received_amount: document.getElementById("totalBftnReceivedAmount"),
        net_cash: document.getElementById("netCash"),
    };
}

const table = document.getElementById('salesTable');
const columns = [
    {
        data: "id",
        title: "ID",
        render: (data, type) => type !== 'display' ? data :
            `<button type="button" class="sale-report-detail-trigger font-semibold text-blue-700 underline decoration-dotted underline-offset-2 hover:text-blue-900" data-modal-target="saleReportDetailModal" data-sale-id="${Number(data) || 0}">#${Number(data) || 'N/A'}</button>`,
    },
    {
        data: "customer_name",
        title: "Customer Name",
        render: (data) => escapeHtml(data || "N/A"),
    },
    {
        data: "customer_mobile",
        title: "Mobile",
        render: (data) => escapeHtml(data || "N/A"),
    },
    {
        data: "ship_name",
        title: "Ship Name",
        render: (data) => data || "N/A",
    },
    {
        data: "number_of_ticket",
        title: "Number Of Tickets",
        render: (data) => data || "0",
    },
    {
        data: null,
        title: "Ticket Price / Other Fee",
        render: (data, type, row) => type !== 'display' ? `${row.ticket_fee} ${row.other_fee}` :
            `<div class="flex flex-col"><span>${formatCurrency(row.ticket_fee)} <small class="text-gray-500">ticket</small></span><span>${formatCurrency(row.other_fee)} <small class="text-gray-500">fee</small></span></div>`,
    },
    {
        data: "total_payable",
        title: "Total Payable",
        render: formatCurrency,
    },
    {
        data: null,
        title: "Received / Refunded",
        render: (data, type, row) => type !== 'display' ? `${row.received_amount} ${row.refunded_amount}` :
            `<div class="flex flex-col"><span>${formatCurrency(row.received_amount)} <small class="text-gray-500">received</small></span><span>${formatCurrency(row.refunded_amount)} <small class="text-gray-500">refunded</small></span></div>`,
    },
    {
        data: "bftn_status",
        title: "BFTN",
        render: (data, type, row) => type !== 'display' ? data :
            `<div class="flex flex-col"><span>${data === 'yes' ? 'Yes' : 'No'}</span>${data === 'yes' ? `<small class="text-gray-500">${row.bftn_received ? 'Received' : 'Pending'}</small>` : ''}</div>`,
    },
    { data: "net_cash", title: "Net Cash", render: formatCurrency },
    {
        data: "due_amount",
        title: "Due Amount",
        render: formatCurrency,
    },
    {
        data: null,
        title: "Action",
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : createActionButtons(row),
    },
];

const filters = () => {
    const filters = {
        ship_id: shipFilter?.value || "",
        company_id: companyFilter?.value || "",
        return_date: returnDateFilter?.value || "",
        payment_method: paymentMethodFilter?.value || "",
        bftn_status: bftnFilter?.value || "",
        start_date: startDateFilter?.value || "",
        end_date: endDateFilter?.value || "",
        start_create_date: startCreateDateFilter?.value || "",
        end_create_date: endCreateDateFilter?.value || "",
    };

    return Object.fromEntries(Object.entries(filters).filter(([, value]) => value));
};

const dataSrc = (json) => {
    updateTotals(json.totals);
    updatePaymentMethodBreakdown(json.totals?.payment_method_totals);

    return json.data || [];
};
initializeDataTable({ table: table, columns, filters, dataSrc });

function formatDate(dateString) {
    if (!dateString || dateString === "Not specified") return dateString || "N/A";

    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
}

function formatCurrency(amount) {
    const numericAmount = Number(String(amount ?? 0).replaceAll(',', ''));

    if (!Number.isFinite(numericAmount)) return "0.00";

    return new Intl.NumberFormat("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numericAmount);
}

function formatDetailValue(value) {
    return value === null || value === undefined || value === '' ? 'N/A' : String(value);
}

function showSaleDetails(sale) {
    const modal = document.getElementById('saleReportDetailModal');
    const details = document.getElementById('saleReportDetails');
    const fields = [
        ['Customer Name', sale.customer_name],
        ['Mobile', sale.customer_mobile],
        ['Company', sale.company_name],
        ['Ship', sale.ship_name],
        ['Journey Date', formatDate(sale.journey_date)],
        ['Return Date', formatDate(sale.return_date)],
        ['Created Date', formatDate(sale.created_at)],
        ['Status', sale.status],
        ['Tickets', sale.number_of_ticket],
        ['Ticket Price', formatCurrency(sale.ticket_fee)],
        ['Other Fee', formatCurrency(sale.other_fee)],
        ['Discount', formatCurrency(sale.discount_amount)],
        ['Total Payable', formatCurrency(sale.total_payable)],
        ['Received Amount', formatCurrency(sale.received_amount)],
        ['Due Amount', formatCurrency(sale.due_amount)],
        ['Extra Received', formatCurrency(sale.extra_received_amount)],
        ['Extra Refunded', formatCurrency(sale.extra_refunded_amount)],
        ['Refund Status', sale.refund_status],
        ['Refunded Tickets', sale.refunded_number_of_tickets],
        ['Gross Ticket Refund', formatCurrency(sale.gross_refund_amount)],
        ['Other Fee Deducted', formatCurrency(sale.other_fee_deduction)],
        ['Refund Discount', formatCurrency(sale.refund_discount_amount)],
        ['Customer Refund Paid', formatCurrency(sale.customer_refund_paid)],
        ['Due Adjusted', formatCurrency(sale.due_adjusted_amount)],
        ['Partner Share', formatCurrency(sale.partner_share_amount)],
        ['Company Retained', formatCurrency(sale.company_retained_amount)],
        ['Extra Payment Refund Paid', formatCurrency(sale.extra_refunded_amount)],
        ['BFTN Status', sale.bftn_status === 'yes' ? (sale.bftn_received ? 'Received' : 'Pending') : 'No'],
        ['BFTN Received Date', sale.bftn_received_at],
        ['BFTN Amount', formatCurrency(sale.bftn_amount)],
        ['Net Cash', formatCurrency(sale.net_cash)],
    ];

    modal.querySelector('h3').textContent = `Sale #${sale.id} Details`;
    const payments = Array.isArray(sale.payments) ? sale.payments : [];
    const paymentHistory = `
        <section class="border-b border-gray-100 pb-3 sm:col-span-2 lg:col-span-3">
            <h4 class="text-xs font-medium uppercase text-gray-500">Payment History (${payments.length})</h4>
            ${payments.length ? `<div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2">${payments.map((payment, index) => `
                <article class="rounded-md border border-gray-200 bg-gray-50 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold text-gray-900">${escapeHtml(payment.payment_method || 'Unknown method')}</span>
                        <span class="font-semibold text-green-700">${formatCurrency(payment.amount)}</span>
                    </div>
                    <dl class="mt-2 grid grid-cols-1 gap-1 text-xs text-gray-600">
                        <div><dt class="inline font-medium">Payment #:</dt> <dd class="inline">${index + 1}</dd></div>
                        <div><dt class="inline font-medium">Date:</dt> <dd class="inline">${escapeHtml(formatDetailValue(payment.payment_datetime || payment.paid_date))}</dd></div>
                        <div><dt class="inline font-medium">Transaction ID:</dt> <dd class="inline break-all">${escapeHtml(formatDetailValue(payment.transaction_id))}</dd></div>
                        ${payment.remark ? `<div><dt class="inline font-medium">Remark:</dt> <dd class="inline break-words">${escapeHtml(payment.remark)}</dd></div>` : ''}
                    </dl>
                </article>`).join('')}</div>` : '<p class="mt-2 text-sm text-gray-500">No payment records.</p>'}
        </section>`;

    details.innerHTML = paymentHistory + fields.map(([label, value]) => `
        <div class="border-b border-gray-100 pb-2">
            <dt class="text-xs font-medium uppercase text-gray-500">${escapeHtml(label)}</dt>
            <dd class="mt-1 break-words text-sm font-medium text-gray-900">${escapeHtml(formatDetailValue(value))}</dd>
        </div>`).join('');
}

function closeSaleDetails() {
    const modal = document.getElementById('saleReportDetailModal');
    modal?._closeModal?.();
}

function updateTotals(totals = {}) {
    const countTotals = new Set([
        'total_number_of_tickets',
        'total_refunded_tickets',
        'total_bftn',
        'total_bftn_pending',
        'total_bftn_received',
    ]);

    Object.entries(totalElements()).forEach(([key, element]) => {
        if (element) element.textContent = totals[key] ?? (countTotals.has(key) ? '0' : '0.00');
    });
}

function updatePaymentMethodBreakdown(methodTotals = []) {
    if (!paymentMethodBreakdownCards || !paymentMethodBreakdownEmpty) return;

    paymentMethodBreakdownCards.replaceChildren();
    const amountsByMethod = new Map(methodTotals.map(({ payment_method, amount }) => [payment_method, amount]));
    const methods = ['Bkash', 'Cash', 'Bank Transfer', 'Nagad'];
    const hasPayments = methods.some((method) => Number(amountsByMethod.get(method) || 0) > 0);
    paymentMethodBreakdownEmpty.classList.toggle('hidden', hasPayments);

    methods.forEach((method) => {
        const card = document.createElement('div');
        card.className = 'rounded-md border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900';

        const label = document.createElement('p');
        label.className = 'text-sm font-medium text-gray-500 dark:text-gray-400';
        label.textContent = method || 'Not specified';

        const value = document.createElement('p');
        value.className = 'mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100';
        value.textContent = formatCurrency(amountsByMethod.get(method) || 0);

        card.append(label, value);
        paymentMethodBreakdownCards.append(card);
    });
}

function createActionButtons(row) {
    if (!row?.id) return "";

    if (config.canEdit) {
        return `
                        <div class="flex gap-2 items-center justify-center">
                            <a data-permission="sales.edit" href="/ship-ticket-sales/${row.id}">
                                <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
                            </a>
                        </div>`;
    } else {
        return "";
    }
}

function reportFilterElements() {
    return [
        shipFilter,
        companyFilter,
        returnDateFilter,
        paymentMethodFilter,
        bftnFilter,
        startDateFilter,
        endDateFilter,
        startCreateDateFilter,
        endCreateDateFilter,
    ];
}

function bindReportTableEvents() {
    reportFilterElements().forEach((filter) => filter?.addEventListener("change", () => refreshDataTable('salesTable')));

    toggleAdvancedFiltersBtn?.addEventListener("click", () => {
        const isExpanded = toggleAdvancedFiltersBtn.getAttribute('aria-expanded') === 'true';
        toggleAdvancedFiltersBtn.setAttribute('aria-expanded', String(!isExpanded));
        advancedFilters.hidden = isExpanded;
    });

    clearFiltersBtn?.addEventListener("click", () => {
        reportFilterElements().forEach((filter) => {
            if (filter) filter.value = "";
        });
        refreshDataTable('salesTable');
    });

    table?.addEventListener('click', (event) => {
        const trigger = event.target.closest('.sale-report-detail-trigger');
        if (!trigger || !window.jQuery || !$.fn.DataTable.isDataTable(table)) return;
        const sale = $(table).DataTable().row(trigger.closest('tr')).data();
        if (sale) showSaleDetails(sale);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeSaleDetails();
    });
}

document.addEventListener("DOMContentLoaded", bindReportTableEvents);
