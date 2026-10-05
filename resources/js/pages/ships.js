import { initializeDataTable } from '../services/data-table.js';
import { createCrudPage } from '../components/crud-page';
import { refreshDataTable } from '../services/api';

const columns = [
    { data: 'id' },
    { data: 'name', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    { data: 'route', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    { data: 'status', render: (data, type) => type !== 'display' ? data : (data == 1 ? 'Active' : 'Inactive') },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `
            <div class="flex gap-2">
                <button type="button" class="editBtn rounded bg-yellow-500 px-2 py-1 text-white" data-id="${row.id}">Edit</button>
                <button type="button" class="deleteBtn rounded bg-red-500 px-2 py-1 text-white" data-id="${row.id}">Delete</button>
                <a href="${document.getElementById('shipsPage').dataset.packagesUrl.replace('__ID__', row.id)}" class="rounded bg-blue-500 px-2 py-1 text-white">Packages</a>
            </div>`,
    },
];
initializeDataTable({ table: document.getElementById('shipsTable'), columns });

document.addEventListener('DOMContentLoaded', () => {
    createCrudPage({
        tableId: 'shipsTable',
        formId: 'shipForm',
        createModalId: 'ship-modal',
        updateModalId: 'ship-modal',
        baseUrl: document.getElementById('shipsPage').dataset.baseUrl,
        fields: ['name', 'route', 'status'],
        getList: () => refreshDataTable('shipsTable'),
    });
});
import { escapeHtml } from '../utils/escape-html';
