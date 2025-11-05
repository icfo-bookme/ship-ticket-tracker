<!-- Update item Modal -->
<div id="update-modal" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">

    <!-- Backdrop (Overlay) -->
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50" id="modal-backdrop"></div>

    <div class="relative p-4 w-full max-w-xl sm:max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit item</h3>
                <button data-modal-hide="update-modal" type="button"
                    class="text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg p-2.5">
                    <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M6.293 4.293a1 1 0 0 1 1.414 0L10 6.586l2.293-2.293a1 1 0 1 1 1.414 1.414L11.414 8l2.293 2.293a1 1 0 1 1-1.414 1.414L10 9.414l-2.293 2.293a1 1 0 1 1-1.414-1.414L8.586 8 6.293 5.707a1 1 0 0 1 0-1.414z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>

            <form id="updateitemForm">
                <input type="hidden" id="update-item-id" name="id">
                <div class="px-6 py-4">
                    <div class="mb-4">
                        <label for="update-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">item
                            Name</label>
                        <input type="text" id="update-name" name="name" required
                            class="block w-full p-2 text-sm text-gray-900 bg-gray-50 rounded-md border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            placeholder="Enter item name">
                    </div>

                    <div class="mb-4">
                        <label for="update-status"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                        <select id="update-status" name="status" required
                            class="block w-full p-2 text-sm text-gray-900 bg-gray-50 rounded-md border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 w-full sm:w-auto">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Show Edit Modal
    function showEditModal(btn) {
        document.getElementById('update-item-id').value = btn.dataset.id;
        document.getElementById('update-name').value = btn.dataset.name;
        document.getElementById('update-status').value = btn.dataset.status;

        // Show the modal
        document.getElementById('update-modal').classList.remove('hidden');
        document.getElementById('update-modal').classList.add('flex');
    }

    // Close the edit modal when the close button is clicked
    document.querySelector('[data-modal-hide="update-modal"]').addEventListener('click', () => {
        closeModal();
    });

    // Close modal when clicking outside the modal content (on the backdrop)
    document.getElementById('modal-backdrop').addEventListener('click', () => {
        closeModal();
    });

    // Function to close the modal
    function closeModal() {
        document.getElementById('update-modal').classList.add('hidden');
        document.getElementById('update-modal').classList.remove('flex');
    }

    // Handle the form submission to update item details
    document.getElementById('updateitemForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('update-item-id').value;
        const data = {
            name: document.getElementById('update-name').value,
            status: document.getElementById('update-status').value
        };

        try {
            // Send a PUT request to update the item
            const response = await fetch(`/companies/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                        'content'),
                },
                body: JSON.stringify(data),
            });

            if (response.status == 200) {
                getList(); // Reload the list after update
                Swal.fire({
                    title: 'Success!',
                    text: 'Item updated successfully!',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'bg-blue-950 text-white'
                    }
                });

                // Close the modal
                closeModal();
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to update the item. Please try again later.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'bg-red-600 text-white'
                    }
                });
            }
        } catch (error) {
            console.error('Error updating item:', error);
            Swal.fire({
                title: 'Error!',
                text: 'There was an error updating the item.',
                icon: 'error',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'bg-red-600 text-white'
                }
            });
        }
    });

    // Add event listener for the Edit buttons
    document.querySelectorAll('.editBtn').forEach(button => {
        button.addEventListener('click', function() {
            showEditModal(this);
        });
    });
</script>
