import { initializeDataTable } from '../services/data-table.js';
import { refreshDataTable } from '../services/api.js';
import { escapeHtml } from '../utils/escape-html';

const table = document.getElementById('companyLossTable');

if (table) {
    const filterFields = {
        ship_id: 'companyLossShip',
        company_id: 'companyLossCompany',
        status: 'companyLossStatus',
        loss_type: 'companyLossType',
        requested_from: 'companyLossFrom',
        requested_to: 'companyLossTo',
    };
    const textColumn = (field) => ({ data: field, render: (value, type) => type === 'display' ? escapeHtml(String(value ?? 'N/A')) : value });
    const moneyColumn = (field) => ({ data: field, render: (value) => Number(value || 0).toFixed(2) });

    initializeDataTable({
        table,
        filters: () => Object.fromEntries(Object.entries(filterFields).map(([key, id]) => [key, document.getElementById(id).value])),
        columns: [
            { data: 'refund_id' }, { data: 'sale_id' }, textColumn('customer_name'),
            textColumn('ship_name'), textColumn('company_name'), textColumn('requested_at'),
            textColumn('status'), moneyColumn('gross_refund_amount'),
            { data: 'loss_type', render: (value) => value === 'partner_share' ? 'Partner Share Loss' : 'Custom Loss' },
            moneyColumn('loss_percent'), moneyColumn('loss_amount'), textColumn('reason'), textColumn('created_by'),
        ],
        dataSrc: (response) => {
            const totals = response.totals || {};
            document.getElementById('companyLossCount').textContent = totals.count ?? 0;
            for (const [key, id] of Object.entries({ total_loss: 'companyLossTotal', pending_loss: 'companyLossPending', completed_loss: 'companyLossCompleted' })) {
                document.getElementById(id).textContent = Number(totals[key] || 0).toFixed(2);
            }
            return response.data || [];
        },
    });

    Object.values(filterFields).forEach((id) => document.getElementById(id).addEventListener('change', () => refreshDataTable(table.id)));
    document.getElementById('clearCompanyLossFilters').addEventListener('click', () => {
        Object.values(filterFields).forEach((id) => { document.getElementById(id).value = ''; });
        refreshDataTable(table.id);
    });
}
