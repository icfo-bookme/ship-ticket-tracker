import { initializeDataTable } from '../services/data-table.js';
import { refreshDataTable } from '../services/api';
import { createCrudPage } from '../components/crud-page';

const columns = [
    { data: 'id' },
    { data: 'spreadsheet_id', render: (data, type, row) => type !== 'display' ? (row.spreadsheetId ?? data) : escapeHtml(row.spreadsheetId ?? data) },
    { data: 'range', render: (data, type) => type !== 'display' ? data : escapeHtml(data) },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `<button type="button" data-permission="excel.edit" class="editBtn rounded bg-yellow-500 px-2 py-1 text-white" data-id="${row.id}">Edit</button>`,
    },
];
initializeDataTable({ table: document.getElementById('excelTable'), columns });

document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('excelSettingsPage');
    createCrudPage({
        tableId: 'excelTable',
        formId: 'excelSettingForm',
        createModalId: 'excel-setting-modal',
        updateModalId: 'excel-setting-modal',
        baseUrl: page.dataset.baseUrl,
        fields: ['spreadsheetId', 'range'],
        getList: () => refreshDataTable('excelTable'),
        transformRecord: (response) => response.data,
    });
});




import { escapeHtml } from '../utils/escape-html';
