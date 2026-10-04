import { refreshDataTable } from '../services/api';
import { createCrudPage } from '../components/crud-page';

document.getElementById('rolesTable').__dataTableColumns = [
    { data: 'id' },
    { data: 'name', render: (data, type, row) => type !== 'display' ? data : `${escapeHtml(data)}${row.is_super_admin ? ' <span class="text-xs text-amber-700">Super Admin</span>' : ''}` },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `
            <div class="flex gap-2">
                <button type="button" class="editBtn rounded bg-yellow-500 px-2 py-1 text-white" data-id="${row.id}">Edit</button>
                <button type="button" class="deleteBtn rounded bg-red-500 px-2 py-1 text-white" data-id="${row.id}" data-is-super-admin="${row.is_super_admin ? 1 : 0}">Delete</button>
            </div>`,
    },
];

document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('rolesPage');
    const permissionInputs = (form) => [...form.querySelectorAll('.role-permission')];

    createCrudPage({
        tableId: 'rolesTable',
        formId: 'roleForm',
        createModalId: 'role-modal',
        updateModalId: 'role-modal',
        baseUrl: page.dataset.baseUrl,
        fields: ['name'],
        getList: () => refreshDataTable('rolesTable'),
        populate: (form, record) => {
            form.elements.namedItem('name').value = record.name ?? '';
            form.elements.namedItem('name').disabled = record.name === page.dataset.superAdminRole;
            permissionInputs(form).forEach((input) => {
                input.checked = (record.permissions || []).some((permission) => permission.name === input.value);
            });
        },
        onCreate: (form) => {
            form.elements.namedItem('name').disabled = false;
            permissionInputs(form).forEach((input) => { input.checked = false; });
        },
        prepareData: (data, form) => ({
            name: data.name,
            permissions: permissionInputs(form).filter((input) => input.checked).map((input) => input.value),
        }),
        canDelete: (button) => {
            if (button.dataset.isSuperAdmin === '1') {
                Swal.fire({ icon: 'warning', title: 'Not allowed', text: 'The Super Admin role cannot be deleted.' });
                return false;
            }
            return true;
        },
    });
});




