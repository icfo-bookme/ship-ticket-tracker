<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between pb-5">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                Ships Details
            </h2>
            <button data-modal-target="add-modal" data-modal-toggle="add-modal"
                class="bg-red-500 text-white px-2 py-1 rounded addBtn">
                + Add New Ship
            </button>
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
                        <th class="border px-4 py-2">Name</th>
                        <th class="border px-4 py-2">Price</th>
                        <th class="border px-4 py-2">round_trip_price</th>
                        <th class="border px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody id="shipsBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Pass the 'id' value from the server-side to JavaScript
    const shipPackageId = @json($id);

    // Now you can use the 'shipPackageId' in your JavaScript fetch request
    const loader = document.getElementById('loader');
    const table = document.getElementById('shipsTable');
    const salesBody = document.getElementById('shipsBody');
    let dataTableInitialized = false;

    async function getList() {
        try {
            loader.style.display = 'block';

            // Use the shipPackageId variable here
            const response = await fetch(`/ship-packages/${shipPackageId}`);
            const data = await response.json();

            loader.style.display = 'none';
            table.classList.remove('hidden');

            salesBody.innerHTML = '';

            // Render ship packages data into the table
            data.forEach(package => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="border border-gray-300 px-4 py-2">${package.id}</td>
                    <td class="border border-gray-300 px-4 py-2">${package.name}</td>
                    <td class="border border-gray-300 px-4 py-2">${package.price}</td>
                    <td class="border border-gray-300 px-4 py-2">${package.round_trip_price}</td>
                    <td class="border border-gray-300 px-4 py-2">
                        <button class="bg-yellow-500 text-white px-2 py-1 rounded editBtn" 
                            data-id="${package.id}" 
                            data-name="${package.name}"
                            data-price="${package.price}"
                            data-round_trip_price="${package.round_trip_price}">
                            Edit  
                        </button>
                        <button class="bg-red-500 text-white px-2 py-1 rounded deleteBtn" 
                            data-id="${package.id}">
                            Delete  
                        </button>
                    </td>
                `;
                salesBody.appendChild(tr);
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

            // Event listeners for edit and delete buttons
            document.querySelectorAll('.editBtn').forEach(btn => {
                btn.addEventListener('click', () => showEditModal(btn));
            });

            document.querySelectorAll('.deleteBtn').forEach(btn => {
                btn.addEventListener('click', () => handleDeleteClick(btn));
            });

        } catch (error) {
            console.error('Error fetching ship packages data:', error);
            loader.textContent = 'Failed to load data. Please try again later.';
        }
    }

    getList();
</script>

