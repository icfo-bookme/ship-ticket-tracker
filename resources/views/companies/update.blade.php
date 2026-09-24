<!-- Update Company Modal -->
<x-entity-modal id="update-modal" title="Edit Company" formId="updateCompanyForm" submitText="Save Changes" maxWidth="md">
    <form id="updateCompanyForm">
        <input type="hidden" id="update-item-id" name="id">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label for="update-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
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
        </div>
    </form>
</x-entity-modal>

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

    // Function to close the modal (delegates to the component's own close
    // handler, which also keeps its internal state in sync).
    function closeModal() {
        const modal = document.getElementById('update-modal');
        if (modal && typeof modal._closeModal === 'function') {
            modal._closeModal();
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    // Handle the form submission to update item details
    document.getElementById('updateCompanyForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('update-item-id').value;
        const data = {
            name: document.getElementById('update-name').value,
            status: document.getElementById('update-status').value
        };

        try {
            // Send a PUT request to update the company
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
                    text: 'Company updated successfully!',
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
                    text: 'Failed to update the company. Please try again later.',
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
                text: 'There was an error updating the company.',
                icon: 'error',
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'bg-red-600 text-white'
                }
            });
        }
    });
</script>
