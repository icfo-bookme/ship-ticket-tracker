<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between pb-5">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Permissions Management
            </h2>
            <button data-modal-target="add-modal" data-modal-toggle="add-modal"
                class="bg-blue-600 text-white px-3 py-1.5 rounded addBtn">
                + Add New Permission
            </button>
        </div>

        <script>
            window.dataTableColumns = window.dataTableColumns || {};
            window.dataTableColumns['permissionsTable'] = [
                { data: 'id', name: 'id' },
                {
                    data: 'name',
                    name: 'name',
                    render: (data, type) => type !== 'display' ? data
                        : `<span class="font-medium">${escapeHtml(data)}</span>`,
                },
                {
                    data: 'group',
                    orderable: false,
                    searchable: false,
                    render: (data, type) => type !== 'display' ? data : escapeHtml(data),
                },
                {
                    data: 'roles',
                    orderable: false,
                    searchable: false,
                    render: (data, type) => {
                        if (type !== 'display') return (data || []).join(', ');
                        return data.length
                            ? data.map(r => `<span class="inline-block bg-purple-100 text-purple-800 text-xs px-2 py-0.5 rounded mr-1 mb-1">${escapeHtml(r)}</span>`).join('')
                            : '<span class="text-gray-400">Not assigned</span>';
                    },
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => {
                        if (type !== 'display') return '';
                        return `
                            <button class="bg-yellow-500 text-white px-2 py-1 rounded editBtn"
                                data-id="${row.id}"
                                data-name="${escapeHtml(row.name)}">
                                Edit
                            </button>
                            <button class="bg-red-500 text-white px-2 py-1 rounded deleteBtn"
                                data-id="${row.id}"
                                data-name="${escapeHtml(row.name)}"
                                data-roles-count="${row.roles_count}">
                                Delete
                            </button>`;
                    },
                },
            ];
        </script>

        <x-data-table id="permissionsTable" :headings="['ID', 'Permission Name', 'Group', 'Assigned Roles', 'Action']" url="/permissions" />
    </div>
</div>
