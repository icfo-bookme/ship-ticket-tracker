<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between pb-5">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Roles Management
            </h2>
            <button data-modal-target="add-modal" data-modal-toggle="add-modal"
                class="bg-blue-600 text-white px-3 py-1.5 rounded addBtn">
                + Add New Role
            </button>
        </div>

        <!-- Loader -->
        <div id="loader" class="text-center my-4 min-h-[100vh]">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Loading data...</p>
        </div>

        <!-- Roles Table -->
        <div class="overflow-x-auto">
            <table id="rolesTable" class="min-w-full border border-gray-300 hidden">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-4 py-2">ID</th>
                        <th class="border px-4 py-2">Role Name</th>
                        <th class="border px-4 py-2">Permissions</th>
                        <th class="border px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody id="rolesBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const loader = document.getElementById('loader');
    const rolesBody = document.getElementById('rolesBody');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Refresh the table after create/update/delete (called by the modal views).
    function getList() {
        if (window.jQuery && $.fn.DataTable.isDataTable('#rolesTable')) {
            $('#rolesTable').DataTable().ajax.reload(null, false);
        }
    }

    // Event delegation — keeps working across DataTables redraws.
    rolesBody.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.editBtn');
        if (editBtn) { showEditModal(editBtn); return; }

        const deleteBtn = e.target.closest('.deleteBtn');
        if (deleteBtn) { handleDeleteClick(deleteBtn); }
    });

    document.addEventListener('DOMContentLoaded', function () {
        // The table must be visible BEFORE DataTables initialises: the global
        // scrollX default (layouts/app.blade.php) clones the thead, and
        // cloning a hidden table yields a zero-height header row.
        loader.style.display = 'none';
        document.getElementById('rolesTable').classList.remove('hidden');

        $('#rolesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '/roles',
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            dom: 'lBfrtip',
            buttons: ['copy', 'excel', 'csv', 'print'],
            order: [[0, 'asc']],
            language: { lengthMenu: '_MENU_', processing: 'Loading data...' },
            columns: [
                { data: 'id', name: 'id' },
                {
                    data: 'name',
                    name: 'name',
                    render: (data, type, row) => type !== 'display' ? data
                        : `<span class="font-medium">${escapeHtml(data)}</span>` +
                          (row.is_super_admin ? ' <span class="inline-block bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded">Super Admin</span>' : ''),
                },
                {
                    data: 'permissions',
                    orderable: false,
                    searchable: false,
                    render: (data, type) => {
                        if (type !== 'display') return (data || []).join(', ');
                        return data.length
                            ? data.map(p => `<span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded mr-1 mb-1">${escapeHtml(p)}</span>`).join('')
                            : '<span class="text-gray-400">No permissions</span>';
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
                                data-name="${escapeHtml(row.name)}"
                                data-permissions="${escapeHtml(JSON.stringify(row.permissions))}"
                                data-is-super-admin="${row.is_super_admin ? 1 : 0}">
                                Edit
                            </button>
                            <button class="bg-red-500 text-white px-2 py-1 rounded deleteBtn"
                                data-id="${row.id}"
                                data-name="${escapeHtml(row.name)}"
                                data-is-super-admin="${row.is_super_admin ? 1 : 0}"
                                ${row.is_super_admin ? 'disabled title="Super Admin role cannot be deleted"' : ''}>
                                Delete
                            </button>`;
                    },
                },
            ],
        });
    });
</script>