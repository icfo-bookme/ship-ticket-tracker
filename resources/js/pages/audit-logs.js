import { initializeDataTable } from '../services/data-table.js';
import { escapeHtml } from '../utils/escape-html.js';

const table = document.getElementById('auditLogsTable');

if (table) {
    initializeDataTable({
        table,
        columns: [
            { data: 'created_at', render: (value) => escapeHtml(value) },
            { data: 'sale_id', render: (value) => value == null ? '—' : escapeHtml(`#${value}`) },
            { data: 'user_name', render: (value) => escapeHtml(value) },
            { data: 'summary', render: (value) => escapeHtml(value) },
        ],
    });
}
