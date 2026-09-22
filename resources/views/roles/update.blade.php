<!-- Edit Role Modal -->
<div id="update-modal" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Edit Role</h3>
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

            <form id="updateRoleForm">
                <input type="hidden" id="update-role-id" name="id">
                <div class="px-6 py-4">
                    <div class="mb-4">
                        <label for="update-role-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role
                            Name</label>
                        <input type="text" id="update-role-name" name="name" required
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>

                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permissions</label>
                        <p class="text-xs text-gray-500 mb-2">Tick the permissions this role should have.</p>
                    </div>

                    <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-md p-3 mb-2">
                        @foreach ($permissionGroups as $group => $perms)
                        <div class="mb-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">{{ $group }}</p>
                            <div class="grid grid-cols-2 gap-1">
                                @foreach ($perms as $perm)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" class="update-permission rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        value="{{ $perm['name'] }}">
                                    <span>{{ $perm['name'] }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
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
        document.getElementById('update-role-id').value = btn.dataset.id;
        document.getElementById('update-role-name').value = btn.dataset.name;
        document.getElementById('update-role-name').disabled = btn.dataset.isSuperAdmin === '1';

        const assigned = JSON.parse(btn.dataset.permissions || '[]');
        document.querySelectorAll('.update-permission').forEach(cb => {
            cb.checked = assigned.includes(cb.value);
        });

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

    // Handle the form submission to update the role
    document.getElementById('updateRoleForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('update-role-id').value;
        const data = {
            name: document.getElementById('update-role-name').value,
            permissions: Array.from(document.querySelectorAll('.update-permission:checked')).map(cb => cb.value),
        };

        try {
            const response = await fetch(`/roles/${id}`, {
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
                    text: result.message || 'Failed to update the role.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                });
            }
        } catch (error) {
            console.error('Error updating role:', error);
            Swal.fire({
                title: 'Error!',
                text: 'There was an error updating the role.',
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    });
</script>