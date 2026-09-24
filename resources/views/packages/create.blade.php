<!-- Modal Structure -->
<x-entity-modal id="add-modal" title="Add New Package" formId="createShipForm" submitText="Save" maxWidth="md">
    <!-- Form for creating a new package -->
    <form id="createShipForm">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Package Name</label>
                <input type="text" name="name" id="name" required placeholder="Enter package name"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
            </div>

            <input type="hidden" name="ship_id" id="ship_id" value="{{ $id }}">

            <div class="mb-4">
                <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Price</label>
                <input type="number" name="price" id="price" required min="0" step="0.01"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
            </div>

            <div class="mb-4">
                <label for="round_trip_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Round Trip Price</label>
                <input type="number" name="round_trip_price" id="round_trip_price" required min="0" step="0.01"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
            </div>
        </div>
    </form>
</x-entity-modal>

<script>
   document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createShipForm');
    const closeButton = document.querySelector('[data-modal-hide="add-modal"]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    form.addEventListener('submit', function(event) {
        event.preventDefault();

        const name = document.getElementById('name').value;
        const price = document.getElementById('price').value;
        const id = document.getElementById('ship_id').value;  
        const round_trip_price = document.getElementById('round_trip_price').value;



        const data = {
            name: name,
            ship_id: id,
            price: price,
            round_trip_price: round_trip_price,
        };

        fetch('/ship-packages', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(data),
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to add ship');
                }
                return response.json();
            })
            .then(data => {
                Swal.fire({
                    title: 'Success!',
                    text: 'Package added successfully!',
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
                    text: 'Failed to add package. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                });
            });
    });
});

</script>
