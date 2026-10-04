import { refreshDataTable } from '../services/api';
import { createCrudPage } from '../components/crud-page';

document.getElementById('permissionsTable').__dataTableColumns = [
    { data: 'id' },
    { data: 'name', render: (data, type) => type !== 'display' ? data : `<span class="font-medium">${escapeHtml(data)}</span>` },
    { data: 'group', render: (data) => escapeHtml(data) },
    { data: 'roles', orderable: false, searchable: false, render: (data, type) => type !== 'display' ? (data || []).join(', ') : (data || []).map((role) => `<span class="mr-1 inline-block rounded bg-purple-100 px-2 py-0.5 text-xs text-purple-800">${escapeHtml(role)}</span>`).join('') || '<span class="text-gray-400">Not assigned</span>' },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `
            <div class="flex gap-2">
                <button type="button" class="editBtn rounded bg-yellow-500 px-2 py-1 text-white" data-id="${row.id}">Edit</button>
                <button type="button" class="deleteBtn rounded bg-red-500 px-2 py-1 text-white" data-id="${row.id}" data-roles-count="${row.roles_count}">Delete</button>
            </div>`,
    },
];

document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('permissionsPage');
    createCrudPage({
        tableId: 'permissionsTable',
        formId: 'permissionForm',
        createModalId: 'permission-modal',
        updateModalId: 'permission-modal',
        baseUrl: page.dataset.baseUrl,
        fields: ['name'],
        getList: () => refreshDataTable('permissionsTable'),
        canDelete: (button) => {
            if (Number(button.dataset.rolesCount) > 0) {
                Swal.fire({ icon: 'warning', title: 'Not allowed', text: 'This permission is assigned to one or more roles.' });
                return false;
            }
            return true;
        },
    });
});




