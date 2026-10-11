import { initializeDataTable } from '../services/data-table.js';
import { apiRequest, refreshDataTable } from '../services/api.js';
import { escapeHtml } from '../utils/escape-html.js';

const table = document.getElementById('deletedSalesTable');
const config = document.getElementById('deletedSalesConfig');

if (table && config) {
    const columns = [
        { data: 'id', render: (value) => `#${Number(value) || ''}` },
        { data: 'customer_name', render: (value) => escapeHtml(value || 'N/A') },
        { data: 'customer_mobile', render: (value) => escapeHtml(value || 'N/A') },
        { data: 'ship_name', render: (value) => escapeHtml(value || 'N/A') },
        { data: 'company_name', render: (value) => escapeHtml(value || 'N/A') },
        { data: 'journey_date', render: (value) => escapeHtml(value || 'N/A') },
        { data: 'status', render: (value) => escapeHtml(value || 'N/A') },
        { data: 'deleted_at', render: (value) => escapeHtml(value || 'N/A') },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: (value, type, sale) => type !== 'display' ? '' : `
                <button type="button" class="restore-deleted-sale rounded bg-green-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500" data-id="${Number(sale.id)}">
                    Restore
                </button>`,
        },
    ];

    initializeDataTable({ table, columns });

    table.addEventListener('click', async (event) => {
        const button = event.target.closest('.restore-deleted-sale');
        if (!button) return;

        const confirmation = await Swal.fire({
            title: 'Restore this sale?',
            text: 'The sale will return to active sales lists and reports.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Restore',
            cancelButtonText: 'Cancel',
        });

        if (!confirmation.isConfirmed) return;

        try {
            const result = await apiRequest(config.dataset.restoreUrl.replace('__ID__', button.dataset.id), {
                method: 'PUT',
            });

            await Swal.fire({ icon: 'success', title: 'Restored', text: result.message });
            refreshDataTable(table.id);
        } catch (error) {
            await Swal.fire({ icon: 'error', title: 'Unable to restore', text: error.message || 'Please try again.' });
        }
    });
}
