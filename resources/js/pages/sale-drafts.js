import { refreshDataTable } from '../services/api';
import { createCrudPage } from '../components/crud-page';

const page = document.getElementById('saleDraftsPage');
const departureDateFilter = document.getElementById('departureDateFilter');
const returnDateFilter = document.getElementById('returnDateFilter');

const dateOnly = (value) => value ? String(value).split('T')[0] : '-';

document.getElementById('saleDraftsTable').__dataTableColumns = [
    { data: 'id' },
    { data: 'departure_date', render: dateOnly },
    { data: 'return_date', render: dateOnly },
    { data: 'details', render: (data) => escapeHtml(data).replace(/\n/g, '<br>') },
    { data: 'note', render: (data) => escapeHtml(data || '-') },
    { data: 'created_at', render: dateOnly },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `<div class="flex gap-2"><button type="button" class="editBtn rounded bg-blue-600 px-2 py-1 text-xs text-white" data-id="${row.id}">Edit</button><button type="button" class="deleteBtn rounded bg-red-600 px-2 py-1 text-xs text-white" data-id="${row.id}">Delete</button></div>`,
    },
];

document.getElementById('saleDraftsTable').__dataTableFilters = () => ({
    departure_date: departureDateFilter.value,
    return_date: returnDateFilter.value,
});

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('clearDraftButton')?.addEventListener('click', () => {
        const form = document.getElementById('saleDraftForm');
        form?.reset();
        if (form) delete form.dataset.recordId;
        document.querySelector('#saleDraftForm [data-submit-label]')?.replaceChildren(
            document.createTextNode('Save Draft'),
        );
    });

    createCrudPage({
        tableId: 'saleDraftsTable',
        formId: 'saleDraftForm',
        baseUrl: page.dataset.baseUrl,
        fields: ['departure_date', 'return_date', 'details', 'note'],
        getList: () => refreshDataTable('saleDraftsTable'),
        onEdit: (form) => {
            form.querySelector('[data-submit-label]')?.replaceChildren(document.createTextNode('Update Draft'));
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });

    departureDateFilter.addEventListener('change', () => refreshDataTable('saleDraftsTable'));
    returnDateFilter.addEventListener('change', () => refreshDataTable('saleDraftsTable'));
    document.getElementById('clearDraftFilters').addEventListener('click', () => {
        departureDateFilter.value = '';
        returnDateFilter.value = '';
        refreshDataTable('saleDraftsTable');
    });
});




