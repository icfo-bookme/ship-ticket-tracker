<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between pb-5">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Cash Collection Details
            </h2>
            <button data-modal-target="add-modal" data-modal-toggle="add-modal"
                class="bg-red-500 text-white px-2 py-1 rounded addBtn">
                + Add New Cash Collection
            </button>
        </div>

        <script>
            window.dataTableColumns = window.dataTableColumns || {};
            window.dataTableColumns['cashCollectionsTable'] = [
                { data: 'id' },
                {
                    data: 'cashout_amount',
                    render: (data, type) => type !== 'display' ? data : escapeHtml(data),
                },
                {
                    data: 'name',
                    render: (data, type) => type !== 'display' ? data : escapeHtml(data),
                },
                {
                    data: 'created_at',
                    render: formatDate,
                },
                {
                    data: 'updated_at',
                    render: formatDate,
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => {
                        if (type !== 'display') return '';

                        return `
                            <button class="bg-yellow-500 text-white px-2 py-1 rounded editBtn"
                                data-id="${escapeHtml(row.id)}"
                                data-name="${escapeHtml(row.name)}"
                                data-cashout="${escapeHtml(row.cashout_amount)}">
                                Edit
                            </button>
                            <button class="bg-red-500 text-white px-2 py-1 rounded deleteBtn"
                                data-id="${escapeHtml(row.id)}">
                                Delete
                            </button>`;
                    },
                },
            ];

            function formatDate(date) {
                if (!date) return '-';
                const parsedDate = new Date(date);
                if (Number.isNaN(parsedDate.getTime())) return '-';

                return parsedDate.toLocaleDateString('en-GB', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                });
            }
        </script>

        <x-data-table id="cashCollectionsTable" :headings="[
            'ID',
            'Cashout Amount',
            'Reason',
            'Created Date',
            'Updated Date',
            'Action',
        ]" url="/cash-collections" />
    </div>
</div>
