<!-- Update Excel Setting Modal -->
<x-entity-modal id="update-modal" title="Edit Excel Setting" formId="updateExcelForm" submitText="Save Changes" maxWidth="lg">
    <form id="updateExcelForm">
        <input type="hidden" id="update-setting-id" name="id">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label for="update-spreadsheet_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Spreadsheet ID</label>
                <input type="text" id="update-spreadsheet_id" name="spreadsheetId" required
                    class="block w-full p-2 text-sm text-gray-900 bg-gray-50 rounded-md border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div class="mb-4">
                <label for="update-range" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Range</label>
                <input type="text" id="update-range" name="range" required
                    class="block w-full p-2 text-sm text-gray-900 bg-gray-50 rounded-md border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
        </div>
    </form>
</x-entity-modal>

<script>
    function showEditModal(btn) {
        document.getElementById('update-setting-id').value = btn.dataset.id;
        document.getElementById('update-spreadsheet_id').value = btn.dataset.spreadsheet_id;
        document.getElementById('update-range').value = btn.dataset.range;

        document.getElementById('update-modal').classList.remove('hidden');
        document.getElementById('update-modal').classList.add('flex');
    }

    // Delegates to the component's own close handler.
    function closeModal() {
        const modal = document.getElementById('update-modal');
        if (modal && typeof modal._closeModal === 'function') {
            modal._closeModal();
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    document.getElementById('updateExcelForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const id = document.getElementById('update-setting-id').value;
        const data = {
            spreadsheetId: document.getElementById('update-spreadsheet_id').value,
            range: document.getElementById('update-range').value,
        };

        try {
            const response = await fetch(`/excel-settings/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(data),
            });

            if (response.ok) {
                Swal.fire({ title: 'Success!', text: 'Excel setting updated successfully!', icon: 'success', confirmButtonText: 'OK' });
                closeModal();
                getList();
            } else {
                Swal.fire({ title: 'Error!', text: 'Failed to update excel setting.', icon: 'error', confirmButtonText: 'OK' });
            }
        } catch (error) {
            console.error('Error updating excel setting:', error);
            Swal.fire({ title: 'Error!', text: 'There was an error updating the excel setting.', icon: 'error', confirmButtonText: 'OK' });
        }
    });
</script>