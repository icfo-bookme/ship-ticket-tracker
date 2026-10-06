import { initializeDataTable } from '../services/data-table.js';
import { refreshDataTable } from '../services/api';
import { createCrudPage } from '../components/crud-page';

const columns = [
    { data: 'id' },
    { data: 'name', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    { data: 'status', render: (data, type) => type !== 'display' ? data : (data == 1 ? 'Active' : 'Inactive') },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `
            <div class="flex gap-2">
                <button type="button" data-permission="companies.edit" class="editBtn rounded bg-yellow-500 px-2 py-1 text-white" data-id="${row.id}">Edit</button>
                <button type="button" data-permission="companies.delete" class="deleteBtn rounded bg-red-500 px-2 py-1 text-white" data-id="${row.id}">Delete</button>
            </div>`,
    },
];
initializeDataTable({ table: document.getElementById('companiesTable'), columns });

document.addEventListener('DOMContentLoaded', () => {
    createCrudPage({
        tableId: 'companiesTable',
        formId: 'companyForm',
        createModalId: 'company-modal',
        updateModalId: 'company-modal',
        baseUrl: document.getElementById('companiesPage').dataset.baseUrl,
        fields: ['name', 'status'],
        getList: () => refreshDataTable('companiesTable'),
    });
});




import { escapeHtml } from '../utils/escape-html';
