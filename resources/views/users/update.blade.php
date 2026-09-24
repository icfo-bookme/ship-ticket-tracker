<!-- Edit User Modal -->
<x-entity-modal id="update-modal" title="Edit User" formId="updateUserForm" submitText="Save Changes" maxWidth="md">
    <form id="updateUserForm">
        <input type="hidden" id="update-user-id" name="id">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label for="update-user-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full
                    Name</label>
                <input type="text" id="update-user-name" name="name" required
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label for="update-user-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                <input type="email" id="update-user-email" name="email" required
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label for="update-user-role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                <select id="update-user-role" name="role" required
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    <option value="">-- Select Role --</option>
                    @foreach ($roles as $role)
                    <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label for="update-user-password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New
                    Password <span class="text-gray-400">(leave blank to keep current)</span></label>
                <input type="password" id="update-user-password" name="password"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
            </div>

            <div class="mb-4" id="update-password-confirm-wrap" style="display: none;">
                <label for="update-user-password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm
                    New Password</label>
                <input type="password" id="update-user-password_confirmation" name="password_confirmation"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
            </div>
        </div>
    </form>
</x-entity-modal>

<script>
    // Show Edit Modal
    function showEditModal(btn) {
        document.getElementById('update-user-id').value = btn.dataset.id;
        document.getElementById('update-user-name').value = btn.dataset.name;
        document.getElementById('update-user-email').value = btn.dataset.email;
        document.getElementById('update-user-role').value = btn.dataset.role || '';
        document.getElementById('update-user-password').value = '';
        document.getElementById('update-user-password_confirmation').value = '';
        document.getElementById('update-password-confirm-wrap').style.display = 'none';

        // Show the modal
        document.getElementById('update-modal').classList.remove('hidden');
        document.getElementById('update-modal').classList.add('flex');
    }

    // Toggle confirm password field only when a new password is typed
    document.getElementById('update-user-password').addEventListener('input', function () {
        document.getElementById('update-password-confirm-wrap').style.display = this.value ? 'block' : 'none';
    });

    // Close the edit modal when the close button is clicked
    document.querySelector('[data-modal-hide="update-modal"]').addEventListener('click', () => {
        closeModal();
    });

    // Function to close the modal
    function closeModal() {
        document.getElementById('update-modal').classList.add('hidden');
        document.getElementById('update-modal').classList.remove('flex');
    }

    // Handle the form submission to update the user
    document.getElementById('updateUserForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('update-user-id').value;
        const password = document.getElementById('update-user-password').value;

        const data = {
            name: document.getElementById('update-user-name').value,
            email: document.getElementById('update-user-email').value,
            role: document.getElementById('update-user-role').value,
        };

        if (password) {
            data.password = password;
            data.password_confirmation = document.getElementById('update-user-password_confirmation').value;
        }

        try {
            const response = await fetch(`/users/${id}`, {
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
                    text: result.message || 'Failed to update the user.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                });
            }
        } catch (error) {
            console.error('Error updating user:', error);
            Swal.fire({
                title: 'Error!',
                text: 'There was an error updating the user.',
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    });
</script>