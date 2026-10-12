import { createCrudPage } from '../components/crud-page';
import { refreshDataTable } from '../services/api';
import { initializeDataTable } from '../services/data-table.js';
import { escapeHtml } from '../utils/escape-html';

const columns = [
    { data: 'id' },
    { data: 'name', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    {
        data: 'is_active',
        render: (data, type) => type !== 'display' ? data : (data ? 'Active' : 'Inactive'),
    },
    { data: 'sort_order' },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `
            <div class="flex gap-2">
                <button type="button" data-permission="sales_sources.edit" class="editBtn rounded bg-amber-500 px-2 py-1 text-white" data-id="${row.id}">Edit</button>
            </div>`,
    },
];

initializeDataTable({ table: document.getElementById('salesSourcesTable'), columns });

document.addEventListener('DOMContentLoaded', () => {
    createCrudPage({
        tableId: 'salesSourcesTable',
        formId: 'salesSourceForm',
        createModalId: 'sales-source-modal',
        updateModalId: 'sales-source-modal',
        baseUrl: document.getElementById('salesSourcesPage').dataset.baseUrl,
        fields: ['name', 'is_active', 'sort_order'],
        populate: (form, record) => {
            form.elements.namedItem('name').value = record.name ?? '';
            form.elements.namedItem('is_active').value = record.is_active ? '1' : '0';
            form.elements.namedItem('sort_order').value = record.sort_order ?? 0;
        },
        onCreate: (form) => {
            form.elements.namedItem('is_active').value = '1';
            form.elements.namedItem('sort_order').value = '0';
        },
        getList: () => refreshDataTable('salesSourcesTable'),
    });
});
