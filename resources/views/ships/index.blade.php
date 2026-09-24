<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between pb-5">
            <h2 class="font-semibold text-xl text-gray-800  leading-tight">
                Ships Details
            </h2>
            <button data-modal-target="add-modal" data-modal-toggle="add-modal"
                class="bg-red-500 text-white px-2 py-1 rounded addBtn">
                + Add New Ship
            </button>
        </div>
        <script>
            window.dataTableColumns = window.dataTableColumns || {};
            window.dataTableColumns['shipsTable'] = [
                { data: 'id' },
                {
                    data: 'name',
                    render: (data, type) => type !== 'display' ? data : escapeHtml(data),
                },
                {
                    data: 'route',
                    render: (data, type) => type !== 'display' ? data : escapeHtml(data),
                },
                {
                    data: 'status',
                    render: (data, type) => type !== 'display' ? data : (data == 1 ? 'Yes' : 'No'),
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
                                data-route="${escapeHtml(row.route)}"
                                data-status="${row.status}">
                                Edit
                            </button>
                            <button class="bg-red-500 text-white px-2 py-1 rounded deleteBtn"
                                data-id="${row.id}">
                                Delete
                            </button>
                            <a href="/ship/packages/${row.id}" class="bg-blue-500 text-white px-2 py-2 rounded addPackagesBtn">
                                Packages
                            </a>`;
                    },
                },
            ];
        </script>

        <x-data-table id="shipsTable" :headings="['ID', 'Name', 'Route', 'Status', 'Action']" url="/ships" />
    </div>
</div>
