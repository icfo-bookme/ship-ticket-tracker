import { initializeDataTable } from '../services/data-table.js';
import { apiRequest, refreshDataTable } from '../services/api';

const page = document.getElementById('extraReceivedPage');
const table = document.getElementById('extraReceivedTable');

if (table && page) {
    const columns = [
        { data: 'id' },
        { data: 'customer_name', render: (data) => escapeHtml(data || 'N/A') },
        { data: 'customer_mobile', render: (data) => escapeHtml(data || 'N/A') },
        { data: 'ship_name', render: (data) => escapeHtml(data || 'N/A') },
        { data: 'journey_date', render: (data) => data ? new Date(data).toLocaleDateString() : 'N/A' },
        { data: 'total_payable', render: formatCurrency },
        { data: 'received_amount', render: formatCurrency },
        { data: 'extra_remaining_amount', render: formatCurrency },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: (data, type, row) => type !== 'display' ? '' : `
                <div class="flex justify-center gap-2">
                    <button type="button" data-permission="extra_received.refund" class="rounded bg-red-600 px-2 py-1 text-white refundExtraBtn" data-id="${row.id}">Refund Extra</button>
                    <button type="button" data-permission="extra_received.adjust" class="rounded bg-blue-600 px-2 py-1 text-white adjustExtraBtn" data-id="${row.id}">Adjust to Other Fee</button>
                </div>`,
        },
    ];
initializeDataTable({ table: table, columns });

    table.addEventListener('click', async (event) => {
        const button = event.target.closest('.adjustExtraBtn, .refundExtraBtn');
        if (!button) return;

        const isRefund = button.classList.contains('refundExtraBtn');
        const confirmation = await Swal.fire({
            title: isRefund ? 'Create refund request?' : 'Adjust extra amount?',
            text: isRefund
                ? 'A refund request will be created for the remaining extra amount.'
                : 'The remaining extra amount will be added to Other Fee.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: isRefund ? 'Yes, create request' : 'Yes, adjust it',
            cancelButtonText: 'Cancel',
        });
        if (!confirmation.isConfirmed) return;

        try {
            const url = (isRefund ? page.dataset.refundUrl : page.dataset.adjustUrl).replace('__ID__', button.dataset.id);
            const result = await apiRequest(url, { method: 'POST' });
            await Swal.fire({ icon: 'success', title: isRefund ? 'Request created' : 'Adjusted', text: result.message });
            refreshDataTable('extraReceivedTable');
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'Action failed',
                text: error.message || Object.values(error.errors || {}).flat().join('\n') || 'Please try again.',
            });
        }
    });
}

function formatCurrency(value) {
    return Number(value || 0).toFixed(2);
}
import { escapeHtml } from '../utils/escape-html';
