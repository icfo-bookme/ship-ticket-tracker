<!-- Add Role Modal -->
<x-entity-modal id="add-modal" title="Add New Role" formId="createRoleForm" submitText="Save" maxWidth="2xl">
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
        </div>
    </form>
</x-entity-modal>

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