<!-- Edit Permission Modal -->
<x-entity-modal id="update-modal" title="Edit Permission" formId="updatePermissionForm" submitText="Save Changes" maxWidth="md">
    <form id="updatePermissionForm">
        <input type="hidden" id="update-permission-id" name="id">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label for="update-permission-name"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permission Name</label>
                <input type="text" id="update-permission-name" name="name" required
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
            </div>
        </div>
    </form>
</x-entity-modal>

<script>
    // Show Edit Modal
    function showEditModal(btn) {
        document.getElementById('update-permission-id').value = btn.dataset.id;
        document.getElementById('update-permission-name').value = btn.dataset.name;

        // Show the modal
        document.getElementById('update-modal').classList.remove('hidden');
        document.getElementById('update-modal').classList.add('flex');
    }

    // Close the edit modal when the close button is clicked
    document.querySelector('[data-modal-hide="update-modal"]').addEventListener('click', () => {
        closeModal();
    });

    // Function to close the modal
    function closeModal() {
        document.getElementById('update-modal').classList.add('hidden');
        document.getElementById('update-modal').classList.remove('flex');
    }

    // Handle the form submission to update the permission
    document.getElementById('updatePermissionForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('update-permission-id').value;
        const data = { name: document.getElementById('update-permission-name').value };

        try {
            const response = await fetch(`/permissions/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();

            if (response.status == 200) {
                Swal.fire({
                    title: 'Success!',
                    text: result.message,
                    icon: 'success',
                    confirmButtonText: 'OK',
                });

                getList();
                closeModal();
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: result.message || 'Failed to update the permission.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                });
            }
        } catch (error) {
            console.error('Error updating permission:', error);
            Swal.fire({
                title: 'Error!',
                text: 'There was an error updating the permission.',
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    });
</script>