import { initializeDataTable } from '../services/data-table.js';
import { apiRequest, refreshDataTable } from '../services/api.js';
import { escapeHtml } from '../utils/escape-html.js';

const table = document.getElementById('bftnReportTable');
const config = document.getElementById('bftnReportConfig');
const filterIds = [
    'bftnReportStatus',
    'bftnReportShip',
    'bftnReportCompany',
    'bftnReportStartDate',
    'bftnReportEndDate',
    'bftnReportReceivedDate',
];

if (table) {
    const filters = () => {
        const receivedDate = document.getElementById('bftnReportReceivedDate').value;

        return {
            status: document.getElementById('bftnReportStatus').value,
            ship_id: document.getElementById('bftnReportShip').value,
            company_id: document.getElementById('bftnReportCompany').value,
            start_date: document.getElementById('bftnReportStartDate').value,
            end_date: document.getElementById('bftnReportEndDate').value,
            received_start_date: receivedDate,
            received_end_date: receivedDate,
        };
    };
    const columns = [
        { data: 'id', render: (value) => `#${Number(value) || ''}` },
        { data: 'customer_name', render: (value) => escapeHtml(value || 'N/A') },
        { data: 'customer_mobile', render: (value) => escapeHtml(value || 'N/A') },
        { data: 'ship_name', render: (value) => escapeHtml(value || 'N/A') },
        { data: 'company_name', render: (value) => escapeHtml(value || 'N/A') },
        { data: 'amount', render: formatMoney },
        { data: 'bftn_date_time', render: formatDateTime },
        {
            data: 'received_status',
            render: (received) => received
                ? '<span class="font-medium text-green-700">Received</span>'
                : '<span class="font-medium text-amber-700">Pending</span>',
        },
        { data: 'received_at', render: formatDateTime },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: (value, type, row) => type !== 'display' || row.received_status || config?.dataset.canReceive !== 'true'
                ? ''
                : `<button type="button" class="bftn-report-receive rounded-md bg-green-700 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-green-800" data-id="${Number(row.id)}" data-bftn-date="${escapeHtml(row.bftn_date_time || '')}">Mark received</button>`,
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
    document.getElementById('clearBftnFilters')?.addEventListener('click', () => {
        filterIds.forEach((id) => {
            const element = document.getElementById(id);
            if (element) element.value = id === 'bftnReportStatus' ? 'all' : '';
        });
        refreshDataTable(table.id);
    });

    table.addEventListener('click', (event) => {
        const button = event.target.closest('.bftn-report-receive');
        if (!button) return;

        document.getElementById('bftnReceivedSaleId').value = button.dataset.id;
        document.getElementById('bftnReceivedAt').value = toDateTimeLocal(button.dataset.bftnDate) || new Date().toISOString().slice(0, 16);
        document.getElementById('bftnReceivedModal')._openModal();
    });

    document.getElementById('bftnReceivedForm')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const saleId = document.getElementById('bftnReceivedSaleId').value;
        const receivedAt = document.getElementById('bftnReceivedAt').value;

        try {
            await apiRequest(`/sale/bftn-received/${encodeURIComponent(saleId)}`, {
                method: 'PUT',
                body: JSON.stringify({ received_at: receivedAt }),
            });
            document.getElementById('bftnReceivedModal')._closeModal();
            await Swal.fire({ icon: 'success', title: 'Updated', text: 'BFTN marked as received.' });
            refreshDataTable(table.id);
        } catch (error) {
            await Swal.fire({ icon: 'error', title: 'Unable to update', text: error.message || 'Please try again.' });
        }
    });
}

function formatMoney(value) {
    const amount = Number(String(value ?? 0).replaceAll(',', ''));

    return Number.isFinite(amount) ? amount.toFixed(2) : '0.00';
}

function formatDateTime(value) {
    if (!value) return 'N/A';
    const date = new Date(String(value).replace(' ', 'T'));

    return Number.isNaN(date.getTime()) ? escapeHtml(value) : escapeHtml(date.toLocaleString());
}

function toDateTimeLocal(value) {
    if (!value) return '';
    const date = new Date(String(value).replace(' ', 'T'));

    return Number.isNaN(date.getTime()) ? '' : new Date(date.getTime() - date.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
}

function updateSummary(totals) {
    const targets = {
        total_count: 'bftnReportTotalCount',
        total_amount: 'bftnReportTotalAmount',
        pending_amount: 'bftnReportPending',
        received_amount: 'bftnReportReceived',
        pending_count: 'bftnReportPendingCount',
        received_count: 'bftnReportReceivedCount',
    };
    Object.entries(targets).forEach(([key, id]) => {
        const element = document.getElementById(id);
        if (element) element.textContent = key.endsWith('_count') ? String(totals[key] ?? 0) : formatMoney(totals[key]);
    });
}
