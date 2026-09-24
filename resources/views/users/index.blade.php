<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between pb-5">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Users Management
            </h2>
            <button data-modal-target="add-modal" data-modal-toggle="add-modal"
                class="bg-blue-600 text-white px-3 py-1.5 rounded addBtn">
                + Add New User
            </button>
        </div>

        <script data-page-columns hidden>
            window.dataTableColumns = window.dataTableColumns || {};
            window.dataTableColumns['usersTable'] = [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'name',
                    name: 'name',
                    render: (data, type, row) => type !== 'display' ? data :
                        `<span class="font-medium">${escapeHtml(data)}</span>` +
                        (row.is_self ?
                            ' <span class="inline-block bg-gray-200 text-gray-700 text-xs px-2 py-0.5 rounded">You</span>' :
                            ''),
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'roles_label',
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => {
                        if (type !== 'display') return data;
                        if (!data) return '<span class="text-gray-400">No role</span>';
                        return `<span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded">${escapeHtml(data)}</span>` +
                            (row.is_super_admin ?
                                ' <span class="inline-block bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded">Super Admin</span>' :
                                '');
                    },
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => {
                        if (type !== 'display') return '';
                        const canDelete = !row.is_super_admin && !row.is_self;
                        return `
                            <button class="bg-yellow-500 text-white px-2 py-1 rounded editBtn"
                                data-id="${row.id}"
                                data-name="${escapeHtml(row.name)}"
                                data-email="${escapeHtml(row.email)}"
                                data-role="${escapeHtml(row.roles_label)}"
                                data-is-super-admin="${row.is_super_admin ? 1 : 0}">
                                Edit
                            </button>
                            <button class="bg-red-500 text-white px-2 py-1 rounded deleteBtn"
                                data-id="${row.id}"
                                data-name="${escapeHtml(row.name)}"
                                data-is-super-admin="${row.is_super_admin ? 1 : 0}"
                                data-is-self="${row.is_self ? 1 : 0}"
                                ${canDelete ? '' : 'disabled title="This account cannot be deleted"'}>
                                Delete
                            </button>`;
                    },
                },
            ];
        </script>

        <x-data-table id="usersTable" :headings="['ID', 'Name', 'Email', 'Role', 'Action']" url="/users" />
    </div>
</div>
