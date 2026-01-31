<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between pb-5">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Whatsapp Details
            </h2>
            
        </div>

        <!-- Loader -->
        <div id="loader" class="text-center my-4">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Loading data...</p>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table id="shipsTable" class="min-w-full border border-gray-300 hidden">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-4 py-2">ID</th>
                        <th class="border px-4 py-2">Tag</th>
                        <th class="border px-4 py-2">Whatsapp Number</th>
                        <th class="border px-4 py-2">Form No</th>
                        <th class="border px-4 py-2">Form Url</th>
                       
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
    const shipsBody = document.getElementById('shipsBody');
    let dataTableInitialized = false;

    async function getList() {
        try {
            loader.style.display = 'block';

            const response = await fetch('/whatsapp');
            const data = await response.json();

            loader.style.display = 'none';
            table.classList.remove('hidden');

            shipsBody.innerHTML = '';

            data.forEach(whatsapp => {
                const tr = document.createElement('tr');

                tr.innerHTML = `
                    <td class="border px-4 py-2">${whatsapp.id}</td>
                    <td class="border px-4 py-2">${whatsapp.tag}</td>
                    <td class="border px-4 py-2">${whatsapp.whatsapp_number}</td>
                    <td class="border px-4 py-2">${whatsapp.form_no}</td>
                    <td class="border px-4 py-2">
                        <div class="flex items-center gap-2">
                            <span class="truncate max-w-[180px]" title="${whatsapp.url}">
                                ${whatsapp.url}
                            </span>
                            <button 
                                class="copyBtn bg-blue-500 text-white px-2 py-1 rounded text-sm"
                                data-url="${whatsapp.url}">
                                Copy
                            </button>
                        </div>
                    </td>
                    
                `;
                shipsBody.appendChild(tr);
            });

            // DataTable init (only once)
            if (!dataTableInitialized) {
                $('#shipsTable').DataTable({
                    dom: 'lBfrtip',
                    lengthChange: true,
                    lengthMenu: [
                        [10, 25, 50, 100, 200],
                        [10, 25, 50, 100, 200]
                    ],
                    buttons: ['copy', 'excel', 'csv', 'pdf', 'print', 'colvis']
                });
                dataTableInitialized = true;
            }

            // Copy URL
            document.querySelectorAll('.copyBtn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const url = btn.dataset.url;
                    navigator.clipboard.writeText(url).then(() => {
                        btn.textContent = 'Copied!';
                        setTimeout(() => btn.textContent = 'Copy', 1500);
                    });
                });
            });

            // Edit & Delete
            document.querySelectorAll('.editBtn').forEach(btn => {
                btn.addEventListener('click', () => showEditModal(btn));
            });

            document.querySelectorAll('.deleteBtn').forEach(btn => {
                btn.addEventListener('click', () => handleDeleteClick(btn));
            });

        } catch (error) {
            console.error(error);
            loader.textContent = 'Failed to load data';
        }
    }

    getList();
</script>
