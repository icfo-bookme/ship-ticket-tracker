import { initializeSearchableMultiSelect } from '../components/searchable-multi-select.js';
import { initializeDataTable } from '../services/data-table.js';
import { escapeHtml } from '../utils/escape-html.js';

const filterContainer = document.getElementById('ticketIssuedReportFilters');
const issuedFromFilter = document.getElementById('ticketIssuedFrom');
const issuedToFilter = document.getElementById('ticketIssuedTo');
const issuedDateOrderFilter = document.getElementById('ticketIssuedDateOrder');
const clearFiltersButton = document.getElementById('clearTicketIssuedFilters');
const table = document.getElementById('ticketIssuedTable');

function updateTotals(totals = {}) {
    const elements = {
        total_sales: 'ticketIssuedTotalSales',
        total_tickets: 'ticketIssuedTotalTickets',
        total_ticket_value: 'ticketIssuedTotalTicketValue',
        total_discount_amount: 'ticketIssuedTotalDiscount',
        total_other_fee: 'ticketIssuedTotalOtherFees',
        total_payable: 'ticketIssuedTotalPayable',
        total_received_amount: 'ticketIssuedTotalReceived',
        total_due_amount: 'ticketIssuedTotalDue',
    };

    Object.entries(elements).forEach(([key, id]) => {
        const element = document.getElementById(id);
        if (!element) return;
        element.textContent = ['total_sales', 'total_tickets'].includes(key)
            ? new Intl.NumberFormat('en-US').format(Number(totals[key]) || 0)
            : formatCurrency(totals[key]);
    });
}

function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        .format(Number(value) || 0);
}

function formatDate(value) {
    if (!value) return 'N/A';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'N/A';
    const dateOnly = /^\d{4}-\d{2}-\d{2}$/.test(value);
    return date.toLocaleString('en-GB', {
        timeZone: dateOnly ? 'UTC' : 'Asia/Dhaka',
        day: '2-digit', month: 'short', year: 'numeric',
        ...(!dateOnly ? { hour: '2-digit', minute: '2-digit' } : {}),
    });
}

function showSaleDetails(sale) {
    const modal = document.getElementById('ticketIssuedDetailModal');
    const fields = [
        ['Customer', sale.customer_name], ['Mobile', sale.customer_mobile],
        ['Ship', sale.ship_name], ['Source Company', sale.company_name],
        ['Journey Date', formatDate(sale.journey_date)], ['Return Date', formatDate(sale.return_date)],
        ['Created At', formatDate(sale.created_at)], ['Issued At', formatDate(sale.issued_at)],
        ['Issued By', sale.issued_by], ['Current Status', sale.status_label],
        ['Tickets', sale.ticket_count], ['Ticket Value', formatCurrency(sale.ticket_value)],
        ['Other Fees', formatCurrency(sale.other_fee)], ['Sales Discount', formatCurrency(sale.discount_amount)],
        ['Total Payable', formatCurrency(sale.total_payable)],
        ['Total Received', formatCurrency(sale.received_amount)], ['Current Due', formatCurrency(sale.due_amount)],
    ];
    modal.querySelector('h3').textContent = `Sale #${sale.id} Details`;
    document.getElementById('ticketIssuedSaleDetails').innerHTML = fields.map(([label, value]) =>
        `<div><dt class="text-xs text-gray-500">${escapeHtml(label)}</dt><dd class="text-sm font-medium text-gray-900 dark:text-gray-100">${escapeHtml(value ?? 'N/A')}</dd></div>`
    ).join('');
    document.getElementById('ticketIssuedTimeline').innerHTML = (sale.verification_timeline || []).map(history =>
        `<article class="rounded-md border border-gray-200 p-3"><p class="text-sm font-medium">${escapeHtml(history.status)}</p><p class="text-xs text-gray-500">${escapeHtml(history.verified_by || 'Unknown user')} · ${escapeHtml(formatDate(history.verified_at))}</p></article>`
    ).join('');
    modal._openModal();
}

function bindReportFilterEvents() {
    if (!filterContainer) return;

    const ships = initializeSearchableMultiSelect(document.getElementById('ticketIssuedShips'));

    const filters = () => ({
        ticket_issued_from: issuedFromFilter.value,
        ticket_issued_to: issuedToFilter.value,
        ship_ids: ships.values(),
        issued_date_order: issuedDateOrderFilter.value,
    });

    if (table) {
        const textColumn = data => ({ data, render: value => escapeHtml(value ?? 'N/A') });
        initializeDataTable({
            table,
            filters,
            dataSrc: json => {
                updateTotals(json.totals);
                return json.data || [];
            },
            columns: [
                { data: 'id', render: (value, type) => type !== 'display' ? value :
                    `<button type="button" class="ticket-issued-detail-trigger font-semibold text-blue-700 underline decoration-dotted underline-offset-2" data-modal-target="ticketIssuedDetailModal">#${Number(value)}</button>` },
                textColumn('customer_name'), textColumn('customer_mobile'), textColumn('ship_name'),
                textColumn('company_name'),
                { data: 'journey_date', render: formatDate },
                textColumn('ticket_count'),
                { data: 'issued_at', render: formatDate },
                textColumn('issued_by'), textColumn('status_label'),
                ...['ticket_value', 'total_payable', 'received_amount', 'due_amount'].map(data => ({
                    data, render: formatCurrency,
                })),
            ],
        });
        table.addEventListener('click', event => {
            const trigger = event.target.closest('.ticket-issued-detail-trigger');
            if (!trigger || !window.jQuery?.fn?.dataTable?.isDataTable(table)) return;
            const sale = window.jQuery(table).DataTable().row(trigger.closest('tr')).data();
            if (sale) showSaleDetails(sale);
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                document.getElementById('ticketIssuedDetailModal')._closeModal();
            }
        });
    }

    function notifyFiltersChanged() {
        issuedToFilter.min = issuedFromFilter.value;
        if (!issuedFromFilter.reportValidity() || !issuedToFilter.reportValidity()) return;

        filterContainer.dispatchEvent(new CustomEvent('ticket-issued-filters-changed', {
            bubbles: true,
            detail: filters(),
        }));
        document.dispatchEvent(new CustomEvent('data-table:refresh', {
            detail: { tableId: 'ticketIssuedTable', resetPaging: true },
        }));
    }

    [issuedFromFilter, issuedToFilter, issuedDateOrderFilter].forEach(filter => {
        filter.addEventListener('change', notifyFiltersChanged);
    });
    filterContainer.addEventListener('multi-select:change', notifyFiltersChanged);
    clearFiltersButton.addEventListener('click', () => {
        [issuedFromFilter, issuedToFilter].forEach(filter => {
            filter.value = '';
        });
        ships.clear({ notify: false });
        issuedDateOrderFilter.value = 'desc';
        if (window.jQuery?.fn?.dataTable?.isDataTable(table)) {
            window.jQuery(table).DataTable().search('');
        }
        notifyFiltersChanged();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindReportFilterEvents, { once: true });
} else {
    bindReportFilterEvents();
}
