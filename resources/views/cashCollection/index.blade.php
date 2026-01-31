<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between pb-5">
            <h2 class="font-semibold text-xl text-gray-800  leading-tight">
                Cash Collection Details
            </h2>
            <button
                data-modal-target="add-modal"
                data-modal-toggle="add-modal"
                class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded addBtn">
                + Add New Cash Collection
            </button>
        </div>

        <!-- Loader -->
        <div id="loader" class="text-center my-6 hidden">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Loading data...</p>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table id="itemTable" class="min-w-full border border-gray-300 hidden">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-4 py-2">ID</th>
                        <th class="border px-4 py-2">Cashout Amount</th>
                        <th class="border px-4 py-2">Reason</th>
                        <th class="border px-4 py-2">Created Date</th>
                        <th class="border px-4 py-2">Updated Date</th>
                        <th class="border px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody id="itemBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const loader = document.getElementById('loader');
    const table = document.getElementById('itemTable');
    const itemBody = document.getElementById('itemBody');
    let dataTable = null;

   function formatDate(date) {
    if (!date) return '-';

    const d = new Date(date);

    return d.toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
}


    async function getList() {
        try {
            loader.classList.remove('hidden');
            table.classList.add('hidden');

            const response = await fetch('/cash-collections');
            const data = await response.json();

            itemBody.innerHTML = '';

            data.forEach(item => {
                const tr = document.createElement('tr');

                tr.innerHTML = `
                    <td class="border px-4 py-2">${item.id}</td>
                    <td class="border px-4 py-2">${item.cashout_amount}</td>
                    <td class="border px-4 py-2">${item.name}</td>
                    <td class="border px-4 py-2">${formatDate(item.created_at)}</td>
                    <td class="border px-4 py-2">${formatDate(item.updated_at)}</td>
                    <td class="border px-4 py-2">
                        <div class="flex gap-2">
                            <button
                                class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded editBtn"
                                data-id="${item.id}">
                                Edit
                            </button>
                            <button
                                class="bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded deleteBtn"
                                data-id="${item.id}">
                                Delete
                            </button>
                        </div>
                    </td>
                `;
                itemBody.appendChild(tr);
            });

            loader.classList.add('hidden');
            table.classList.remove('hidden');

            // Reinitialize DataTable safely
            if (dataTable) {
                dataTable.destroy();
            }

            dataTable = $('#itemTable').DataTable({
                dom: 'lBfrtip',
                lengthMenu: [
                    [10, 25, 50, 100, 200, 500],
                    [10, 25, 50, 100, 200, 500]
                ],
                language: {
                    lengthMenu: '_MENU_'
                },
                buttons: [
                    'copy', 'excel', 'csv', 'pdf', 'print',
                    {
                        extend: 'colvis',
                        text: 'Column Visibility'
                    }
                ]
            });

            // Edit button
            document.querySelectorAll('.editBtn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    showEditModal(id);
                });
            });

            // Delete button
            document.querySelectorAll('.deleteBtn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.id;
                    handleDeleteClick(id);
                });
            });

        } catch (error) {
            console.error(error);
            loader.innerHTML = '<p class="text-red-600">Failed to load data.</p>';
        }
    }

    getList();
</script>
