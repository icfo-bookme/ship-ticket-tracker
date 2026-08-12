<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between pb-5">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                Excel Setting
            </h2>
        </div>
        <!-- Loader -->
        <div id="loader" class="text-center my-4">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Loading data...</p>
        </div>

        <div class="overflow-x-auto">
            <table id="excelTable" class="min-w-full border border-gray-300 hidden">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-4 py-2">ID</th>
                        <th class="border px-4 py-2">Spreadsheet ID</th>
                        <th class="border px-4 py-2">Range</th>
                        <th class="border px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody id="excelBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const loader = document.getElementById('loader');
    const table = document.getElementById('excelTable');
    const excelBody = document.getElementById('excelBody');
    let dataTableInitialized = false;

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async function getList() {
        try {
            loader.style.display = 'block';

            const response = await fetch('/excel-settings');
            const data = await response.json();

            loader.style.display = 'none';
            table.classList.remove('hidden');

            excelBody.innerHTML = '';

            data.forEach(setting => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="border border-gray-300 px-4 py-2">${setting.id}</td>
                    <td class="border border-gray-300 px-4 py-2">${escapeHtml(setting.spreadsheetId)}</td>
                    <td class="border border-gray-300 px-4 py-2">${escapeHtml(setting.range)}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        <button class="bg-yellow-500 text-white px-2 py-1 rounded editBtn"
                            data-id="${setting.id}"
                            data-spreadsheet_id="${escapeHtml(setting.spreadsheetId)}"
                            data-range="${escapeHtml(setting.range)}">
                            Edit
                        </button>
                    </td>
                `;
                excelBody.appendChild(tr);
            });

            if (!dataTableInitialized) {
                $('#excelTable').DataTable({
                    dom: 'lBfrtip',
                    lengthChange: true,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    language: { lengthMenu: '_MENU_' },
                    buttons: ['copy', 'excel', 'csv', 'pdf', 'print', { extend: 'colvis', text: 'Column Visibility' }]
                });
                dataTableInitialized = true;
            }

            document.querySelectorAll('.editBtn').forEach(btn => {
                btn.addEventListener('click', () => showEditModal(btn));
            });
        } catch (error) {
            console.error('Error fetching excel settings:', error);
            loader.textContent = 'Failed to load data. Please try again later.';
        }
    }

    getList();
</script>