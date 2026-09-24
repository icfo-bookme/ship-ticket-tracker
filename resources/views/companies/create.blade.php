<!-- Modal Structure -->
<x-entity-modal id="add-modal" title="Add New Company" formId="createCompanyForm" submitText="Save" maxWidth="md">
    <!-- Form for creating a new item -->
    <form id="createCompanyForm">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Name</label>
                <input type="text" name="name" id="name" required
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label for="status"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select name="status" id="status" required
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    <option value=1>Active</option>
                    <option value=0>Inactive</option>
                </select>
            </div>
        </div>
    </form>
</x-entity-modal>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('createCompanyForm');
        const closeButton = document.querySelector('[data-modal-hide="add-modal"]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        form.addEventListener('submit', function(event) {
            event.preventDefault();

            const name = document.getElementById('name').value;
            const status = document.getElementById('status').value;

            const data = {
                name: name,
                status: status
            };

            fetch('/companies', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(data),
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to add item');
                    }
                    return response.json();
                })
                .then(data => {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Company Added successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-blue-950 text-white'
                        }
                    });

                    getList();

                    form.reset();
                    closeButton.click();
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to add company. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                    });
                });
        });


    });
</script>
