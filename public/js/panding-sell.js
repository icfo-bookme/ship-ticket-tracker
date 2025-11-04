document.addEventListener('DOMContentLoaded', () => {
    const loader = document.getElementById('loader');
    const table = document.getElementById('salesTable');
    const salesBody = document.getElementById('salesBody');

    let dataTableInitialized = false;

    async function getList() {
        try {
            loader.style.display = 'block';

            const response = await fetch('/sales/pending');
            const data = await response.json();

            loader.style.display = 'none';
            table.classList.remove('hidden');

            salesBody.innerHTML = '';

            // Render sales data into the table
            data.forEach(sale => {
                const tr = document.createElement('tr');
                const formatDate = (field, value) => {
                    if (['journey_date', 'issued_date'].includes(field) && value !== 'Not specified') {
                        return new Date(value).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    }
                    return value;
                };
                tr.innerHTML = `
                    <td class="border px-4 py-2">${sale.id}</td>
                    <td class="border px-4 py-2">${sale.customer_name}</td>
                    <td class="border px-4 py-2">${sale.customer_mobile}</td>
                    <td class="border px-4 py-2">${sale.ship_name}</td>
                    <td class="border px-4 py-2">${formatDate('journey_date', sale.journey_date)}</td>
                    <td class="border px-4 py-2">${sale.ticket_fee}</td>
                    <td class="border px-4 py-2">${sale.status}</td>
                    <td class="border px-4 py-2">
                        <button class="bg-yellow-500 text-white px-2 py-1 rounded editBtn" 
                            data-id="${sale.id}" 
                            data-customer="${sale.customer_name}" 
                            data-mobile="${sale.customer_mobile}" 
                            data-status="${sale.status}">
                            Edit  
                        </button>
                        <button class="bg-red-500 text-white px-2 py-1 rounded deleteBtn" 
                            data-id="${sale.id}">
                            Delete  
                        </button>
                    </td>
                `;
                salesBody.appendChild(tr);
            });

            // Initialize DataTable if not already initialized
            if (!dataTableInitialized) {
                $('#salesTable').DataTable({
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

            document.querySelectorAll('.deleteBtn').forEach(btn => {
                btn.addEventListener('click', () => deleteSale(btn));
            });

        } catch (error) {
            console.error('Error fetching sales data:', error);
            loader.textContent = 'Failed to load data. Please try again later.';
        }
    }

    getList();

    // Show Edit Modal
    function showEditModal(btn) {
        document.getElementById('editId').value = btn.dataset.id;
        document.getElementById('editCustomerName').value = btn.dataset.customer;
        document.getElementById('editMobile').value = btn.dataset.mobile;
        document.getElementById('editStatus').value = btn.dataset.status;

        // Show the edit modal
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editModal').classList.add('flex');
    }

    // Close the edit modal
    document.getElementById('closeModal').addEventListener('click', () => {
        document.getElementById('editModal').classList.add('hidden');
        document.getElementById('editModal').classList.remove('flex');
    });

    document.getElementById('editForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('editId').value;
        const data = {
            customer_name: document.getElementById('editCustomerName').value,
            customer_mobile: document.getElementById('editMobile').value,
            status: document.getElementById('editStatus').value
        };

        updateSale(id, data);
    });

    // Function to update sale
    async function updateSale(id, data) {
        try {
            // Send PUT request to update sale status
            const response = await fetch(`/sales/status/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            // Show success message
            Swal.fire({
                title: 'Success!',
                text: 'Sale updated successfully!',
                icon: 'success',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'bg-blue-950 text-white'
                }
            });

            // Refresh sales list
            getList();

            // Close the modal after updating
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');

        } catch (error) {
            console.error('Error updating sale:', error);
            alert('Failed to update sale. Please try again.');
        }
    }

    // Function to delete sale
    async function deleteSale(btn) {
        const saleId = btn.dataset.id;

        // Confirm with the user
        const isConfirmed = confirm('Are you sure you want to delete this sale?');
        if (isConfirmed) {
            try {
                // Send DELETE request to delete the sale
                const response = await fetch(`/sale/delete/${saleId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                });

                const result = await response.json();
                if (result.success) {
                    // Show success message
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Sale has been successfully deleted.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-blue-950 text-white'
                        }
                    });

                    // Reload the sales list
                    getList();
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to delete sale. Please try again later.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-red-600 text-white'
                        }
                    });
                }
            } catch (error) {
                console.error('Error deleting sale:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while deleting the sale.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'bg-red-600 text-white'
                    }
                });
            }
        }
    }
});
