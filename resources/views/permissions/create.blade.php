<!-- Add Permission Modal -->
<x-entity-modal id="add-modal" title="Add New Permission" formId="createPermissionForm" submitText="Save" maxWidth="md">
    <!-- Form for creating a new permission -->
    <form id="createPermissionForm">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permission
                    Name</label>
                <input type="text" name="name" id="name" required
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600"
                    placeholder="e.g. sales.create, reports.view">
            </div>
            <p class="text-xs text-gray-500 mb-2">
                Convention: <code>module.action</code> — e.g. <code>sales.create</code>, <code>refunds.manage</code>.
                Use lowercase words separated by dots.
            </p>
        </div>
    </form>
</x-entity-modal>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('createPermissionForm');
        const closeButton = document.querySelector('[data-modal-hide="add-modal"]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            fetch('/permissions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ name: document.getElementById('name').value }),
            })
                .then(async response => {
                    const result = await response.json();
                    if (!response.ok) {
                        throw new Error(result.message || 'Failed to add permission');
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
                        text: error.message || 'Failed to add permission. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                    });
                });
        });
    });
</script>