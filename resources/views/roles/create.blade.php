<!-- Add Role Modal -->
<div id="add-modal" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
            <div
                class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Add New Role</h3>
                <button data-modal-hide="add-modal" type="button"
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

            <!-- Form for creating a new role -->
            <form id="createRoleForm">
                <div class="px-6 py-4">
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role
                            Name</label>
                        <input type="text" name="name" id="name" required
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600"
                            placeholder="e.g. Manager, Agent">
                    </div>

                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permissions</label>
                        <p class="text-xs text-gray-500 mb-2">Select the permissions this role should have.</p>
                    </div>

                    <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-md p-3 mb-2">
                        @foreach ($permissionGroups as $group => $perms)
                        <div class="mb-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">{{ $group }}</p>
                            <div class="grid grid-cols-2 gap-1">
                                @foreach ($perms as $perm)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" class="create-permission rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        value="{{ $perm['name'] }}">
                                    <span>{{ $perm['name'] }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                        @if ($permissionGroups->isEmpty())
                        <p class="text-sm text-gray-500">No permissions exist yet. Create permissions first.</p>
                        @endif
                    </div>

                    <div class="mt-4 text-right">
                        <button type="submit"
                            class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-400">Save</button>
                        <button type="button" data-modal-hide="add-modal"
                            class="ml-2 px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('createRoleForm');
        const closeButton = document.querySelector('[data-modal-hide="add-modal"]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const name = document.getElementById('name').value;
            const permissions = Array.from(document.querySelectorAll('.create-permission:checked'))
                .map(cb => cb.value);

            fetch('/roles', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ name: name, permissions: permissions }),
            })
                .then(async response => {
                    const result = await response.json();
                    if (!response.ok) {
                        throw new Error(result.message || 'Failed to add role');
                    }
                    return result;
                })
                .then(result => {
                    Swal.fire({
                        title: 'Success!',
                        text: result.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        customClass: { confirmButton: 'bg-blue-950 text-white' }
                    });

                    getList();
                    form.reset();
                    closeButton.click();
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: error.message || 'Failed to add role. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                    });
                });
        });
    });
</script>