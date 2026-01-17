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

        <!-- Sales Table -->
        <div class="overflow-x-auto">
            <table id="shipsTable" class="min-w-full border border-gray-300 hidden">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-4 py-2">ID</th>
                        <th class="border px-4 py-2">spreadsheetId</th>
                        <th class="border px-4 py-2">range</th>
                        <th class="border px-4 py-2">Status</th>
                        <th class="border px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody id="shipsBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const loader = document.getElementById('loader');
    const table = document.getElementById('shipsTable');
    const salesBody = document.getElementById('shipsBody');
    let dataTableInitialized = false;

    async function getList() {
        try {
            loader.style.display = 'block';

            const response = await fetch('/excel-settings');
            const data = await response.json();

            loader.style.display = 'none';
            table.classList.remove('hidden');

            salesBody.innerHTML = '';

            data.forEach(excel => {
                const status = excel.status == 1 ? 'Yes' : 'No';
                const tr = document.createElement('tr');

                tr.innerHTML = `
                    <td class="border border-gray-300 px-4 py-2">${excel.id}</td>
                    <td class="border border-gray-300 px-4 py-2">${excel.spreadsheetId}</td>
                    <td class="border border-gray-300 px-4 py-2">${excel.range}</td>
                    <td class="border border-gray-300 px-4 py-2">${status}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        <button class="bg-yellow-500 text-white px-2 py-1 rounded editBtn" 
                            data-id="${excel.id}" 
                            data-name="${excel.name}" 
                            data-route="${excel.route}" 
                            data-status="${excel.status}">
                            Edit  
                        </button>
                        
                        
                    </td>
                `;
                shipsBody.appendChild(tr);
            });

            // Initialize DataTable if not already initialized
            if (!dataTableInitialized) {
                $('#shipsTable').DataTable({
                    dom: 'lBfrtip',
                    lengthChange: true,
                    lengthMenu: [
                        [10, 25, 50, 75, 100, 200, 300, 400, 500],
                        [10, 25, 50, 75, 100, 200, 300, 400, 500]
                    ],
                    language: {
                        lengthMenu: '_MENU_' // Display dropdown only
                    },
                    buttons: [
                        'copy', 'excel', 'csv', 'pdf', 'print',
                        {
                            extend: 'colvis',
                            text: 'Column Visibility'
                        }
                    ]
                });
                dataTableInitialized = true;
            }
            document.querySelectorAll('.editBtn').forEach(btn => {
                btn.addEventListener('click', () => showEditModal(btn));
            });

          



        } catch (error) {
            console.error('Error fetching sales data:', error);
            loader.textContent = 'Failed to load data. Please try again later.';
        }
    }

    getList();
</script>
