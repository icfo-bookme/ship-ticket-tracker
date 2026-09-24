<!-- Update Package Modal -->
<x-entity-modal id="update-modal" title="Edit Package" formId="updateShipForm" submitText="Save Changes" maxWidth="lg">
    <form id="updateShipForm">
        <input type="hidden" id="update-package-id" name="id">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label for="update-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Package Name</label>
                <input type="text" id="update-name" name="name" required
                    class="block w-full p-2 text-sm text-gray-900 bg-gray-50 rounded-md border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="Enter package name">
            </div>
            <div class="mb-4">
                <label for="update-price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Price</label>
                <input type="text" id="update-price" name="price" required
                    class="block w-full p-2 text-sm text-gray-900 bg-gray-50 rounded-md border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="Enter Price">
            </div>
            <div class="mb-4">
                <label for="update-round_trip_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Round Trip Price</label>
                <input type="number" id="update-round_trip_price" name="round_trip_price" required
                    class="block w-full p-2 text-sm text-gray-900 bg-gray-50 rounded-md border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                    placeholder="Enter round_trip_price">
            </div>
        </div>
    </form>
</x-entity-modal>

<script>
    // Show Edit Modal
    function showEditModal(btn) {

        document.getElementById('update-package-id').value = btn.dataset.id;
        document.getElementById('update-name').value = btn.dataset.name;
        document.getElementById('update-price').value = btn.dataset.price;
        document.getElementById('update-round_trip_price').value = btn.dataset.round_trip_price;

        // Show the modal
        document.getElementById('update-modal').classList.remove('hidden');
        document.getElementById('update-modal').classList.add('flex');
    }

    // Function to close the modal (delegates to the component close handler)
    function closeModal() {
        const modal = document.getElementById('update-modal');
        if (modal && typeof modal._closeModal === 'function') {
            modal._closeModal();
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    // Handle the form submission to update package details
    document.getElementById('updateShipForm').addEventListener('submit', async (e) => {
        e.preventDefault(); // Prevent form from refreshing the page

        const id = document.getElementById('update-package-id').value;
        const data = {
            name: document.getElementById('update-name').value,
            price: document.getElementById('update-price').value,
        round_trip_price: document.getElementById('update-round_trip_price').value,

        };

        try {
            // Send a PUT request to update the ship
            const response = await fetch(`/ship-packages/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content'),
                },
                body: JSON.stringify(data),
            });

            if (response.status == 200) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Package updated successfully!',
                    icon: 'success',
                    confirmButtonText: 'OK',
                });

                getList();

                // Close the modal
                closeModal();
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to update the package. Please try again later.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                });
            }
        } catch (error) {
            console.error('Error updating package:', error);
            Swal.fire({
                title: 'Error!',
                text: 'There was an error updating the package.',
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    });

    // Edit buttons are wired via index.blade.php after each render.
</script>
