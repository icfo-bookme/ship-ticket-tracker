import { initializeDataTable } from '../services/data-table.js';
import { refreshDataTable } from '../services/api';
import { createCrudPage } from '../components/crud-page';

const columns = [
    { data: 'id' },
    { data: 'name', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    { data: 'price', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    { data: 'round_trip_price', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `
            <div class="flex gap-2">
                <button type="button" data-permission="packages.edit" class="editBtn rounded bg-yellow-500 px-2 py-1 text-white" data-id="${row.id}">Edit</button>
                <button type="button" data-permission="packages.delete" class="deleteBtn rounded bg-red-500 px-2 py-1 text-white" data-id="${row.id}">Delete</button>
            </div>`,
    },
];
initializeDataTable({ table: document.getElementById('packagesTable'), columns });

document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('packagesPage');
    createCrudPage({
        tableId: 'packagesTable',
        formId: 'packageForm',
        createModalId: 'package-modal',
        updateModalId: 'package-modal',
        baseUrl: page.dataset.baseUrl,
        recordUrl: page.dataset.recordUrl,
        fields: ['name', 'price', 'round_trip_price'],
        getList: () => refreshDataTable('packagesTable'),
    });
});




import { escapeHtml } from '../utils/escape-html';
