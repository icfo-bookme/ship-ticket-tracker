import { initializeDataTable } from '../services/data-table.js';
import { refreshDataTable } from '../services/api';
import { createCrudPage } from '../components/crud-page';

const formatDate = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '-' : date.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
};

const columns = [
    { data: 'id' },
    { data: 'cashout_amount', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    { data: 'name', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    { data: 'created_at', render: formatDate },
    { data: 'updated_at', render: formatDate },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `<div class="flex gap-2"><button type="button" class="editBtn rounded bg-yellow-500 px-2 py-1 text-white" data-id="${row.id}">Edit</button><button type="button" class="deleteBtn rounded bg-red-500 px-2 py-1 text-white" data-id="${row.id}">Delete</button></div>`,
    },
];
initializeDataTable({ table: document.getElementById('cashCollectionsTable'), columns });

document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('cashCollectionsPage');
    const availableCash = page.dataset.availableCash;

    createCrudPage({
        tableId: 'cashCollectionsTable',
        formId: 'cashCollectionForm',
        createModalId: 'cash-collection-modal',
        updateModalId: 'cash-collection-modal',
        baseUrl: page.dataset.baseUrl,
        fields: ['name', 'cashout_amount'],
        getList: () => refreshDataTable('cashCollectionsTable'),
        onCreate: (form) => {
            form.elements.namedItem('available_cash_amount').value = availableCash;
        },
        prepareData: (data) => {
            delete data.available_cash_amount;
            return data;
        },
    });
});




import { escapeHtml } from '../utils/escape-html';
