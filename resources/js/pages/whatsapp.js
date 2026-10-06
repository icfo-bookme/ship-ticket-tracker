import { initializeDataTable } from '../services/data-table.js';
import { refreshDataTable } from '../services/api';
import { createCrudPage } from '../components/crud-page';

const columns = [
    { data: 'id' },
    { data: 'tag', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    { data: 'whatsapp_number', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    { data: 'form_no', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    {
        data: 'url',
        orderable: false,
        searchable: false,
        render: (data, type) => type !== 'display' ? data : `<div class="flex items-center gap-2"><span class="max-w-[180px] truncate" title="${escapeHtml(data)}">${escapeHtml(data)}</span><button type="button" class="copyBtn rounded bg-blue-500 px-2 py-1 text-sm text-white" data-url="${escapeHtml(data)}">Copy</button></div>`,
    },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `<div class="flex gap-2"><button type="button" data-permission="whatsapp.edit" class="editBtn rounded bg-yellow-500 px-2 py-1 text-white" data-id="${row.id}">Edit</button><button type="button" data-permission="whatsapp.delete" class="deleteBtn rounded bg-red-500 px-2 py-1 text-white" data-id="${row.id}">Delete</button></div>`,
    },
];
initializeDataTable({ table: document.getElementById('whatsappTable'), columns });

document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('whatsappPage');
    createCrudPage({
        tableId: 'whatsappTable',
        formId: 'whatsappForm',
        createModalId: 'whatsapp-modal',
        updateModalId: 'whatsapp-modal',
        baseUrl: page.dataset.baseUrl,
        fields: ['tag', 'whatsapp_number', 'form_no', 'url'],
        getList: () => refreshDataTable('whatsappTable'),
    });

    document.getElementById('whatsappTableBody').addEventListener('click', (event) => {
        const button = event.target.closest('.copyBtn');
        if (!button) return;
        navigator.clipboard.writeText(button.dataset.url).then(() => {
            button.textContent = 'Copied!';
            setTimeout(() => { button.textContent = 'Copy'; }, 1500);
        });
    });
});




import { escapeHtml } from '../utils/escape-html';
