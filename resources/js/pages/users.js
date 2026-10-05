import { initializeDataTable } from '../services/data-table.js';
import { refreshDataTable } from '../services/api';
import { createCrudPage } from '../components/crud-page';

const columns = [
    { data: 'id' },
    { data: 'name', render: (data, type, row) => type !== 'display' ? data : `${escapeHtml(data)}${row.is_self ? ' <span class="text-xs text-gray-500">You</span>' : ''}` },
    { data: 'email', render: (data) => escapeHtml(data) },
    { data: 'roles_label', render: (data, type, row) => type !== 'display' ? data : `${escapeHtml(data || 'No role')}${row.is_super_admin ? ' <span class="text-xs text-amber-700">Super Admin</span>' : ''}` },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `
            <div class="flex gap-2">
                <button type="button" class="editBtn rounded bg-yellow-500 px-2 py-1 text-white" data-id="${row.id}">Edit</button>
                <button type="button" class="deleteBtn rounded bg-red-500 px-2 py-1 text-white" data-id="${row.id}" data-is-self="${row.is_self ? 1 : 0}" data-is-super-admin="${row.is_super_admin ? 1 : 0}">Delete</button>
            </div>`,
    },
];
initializeDataTable({ table: document.getElementById('usersTable'), columns });

document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('usersPage');
    const passwordFields = (form) => ({
        password: form.elements.namedItem('password'),
        confirmation: form.elements.namedItem('password_confirmation'),
    });

    createCrudPage({
        tableId: 'usersTable',
        formId: 'userForm',
        createModalId: 'user-modal',
        updateModalId: 'user-modal',
        baseUrl: page.dataset.baseUrl,
        fields: ['name', 'email', 'role', 'password', 'password_confirmation'],
        getList: () => refreshDataTable('usersTable'),
        populate: (form, record) => {
            form.elements.namedItem('name').value = record.name ?? '';
            form.elements.namedItem('email').value = record.email ?? '';
            form.elements.namedItem('role').value = record.roles?.[0]?.name ?? '';
            form.elements.namedItem('password').value = '';
            form.elements.namedItem('password_confirmation').value = '';
            passwordFields(form).password.required = false;
            passwordFields(form).confirmation.required = false;
            passwordFields(form).confirmation.closest('[data-password-confirmation]')?.classList.add('hidden');
        },
        onCreate: (form) => {
            passwordFields(form).password.required = true;
            passwordFields(form).confirmation.required = true;
            passwordFields(form).confirmation.closest('[data-password-confirmation]')?.classList.remove('hidden');
        },
        prepareData: (data) => {
            if (!data.password) {
                delete data.password;
                delete data.password_confirmation;
            }

            return data;
        },
        canDelete: (button) => {
            if (button.dataset.isSelf === '1' || button.dataset.isSuperAdmin === '1') {
                Swal.fire({ icon: 'warning', title: 'Not allowed', text: button.dataset.isSelf === '1' ? 'You cannot delete your own account.' : 'The Super Admin account cannot be deleted.' });
                return false;
            }
            return true;
        },
    });
});




import { escapeHtml } from '../utils/escape-html';
