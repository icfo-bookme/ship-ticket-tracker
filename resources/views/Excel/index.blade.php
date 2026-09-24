<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between pb-5">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                Excel Setting
            </h2>
        </div>
        <script>
            window.dataTableColumns = window.dataTableColumns || {};
            window.dataTableColumns['excelTable'] = [
                { data: 'id' },
                {
                    data: 'spreadsheet_id',
                    render: (data, type, row) => {
                        const value = row.spreadsheetId ?? row.spreadsheet_id;
                        return type !== 'display' ? value : escapeHtml(value);
                    },
                },
                {
                    data: 'range',
                    render: (data, type) => type !== 'display' ? data : escapeHtml(data),
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => {
                        if (type !== 'display') return '';
                        const spreadsheetId = row.spreadsheetId ?? row.spreadsheet_id;
                        return `
                            <button class="bg-yellow-500 text-white px-2 py-1 rounded editBtn"
                                data-id="${row.id}"
                                data-spreadsheet_id="${escapeHtml(spreadsheetId)}"
                                data-range="${escapeHtml(row.range)}">
                                Edit
                            </button>`;
                    },
                },
            ];
        </script>

        <x-data-table id="excelTable" :headings="['ID', 'Spreadsheet ID', 'Range', 'Action']" url="/excel-settings" />
    </div>
</div>
