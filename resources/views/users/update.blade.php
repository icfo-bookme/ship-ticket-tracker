<!-- Edit User Modal -->
<div id="update-modal" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full sm:max-w-md">
        <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit User</h3>
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

                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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