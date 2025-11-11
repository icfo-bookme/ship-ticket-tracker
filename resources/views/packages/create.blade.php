<!-- Modal Structure -->
<div id="add-modal" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full sm:max-w-md">
        <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
            <div
                class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Add New Ship</h3>
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

            <!-- Form for creating a new ship -->
            <form id="createShipForm">
                <div class="px-6 py-4">
                    <div class="mb-4 hidden">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">ship
                            id</label>
                        <input type="int" name="ship_id" id="ship_id" value={{ $id }} required
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>
                    <div class="mb-4">
                        <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Price</label>
                        <input type="number" name="price" id="price" required
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>

                    <div class="mb-4">
                        <label for="round_trip_price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            round_trip_price</label>
                        <input type="number" name="round_trip_price" id="round_trip_price" required
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Package
                            Name</label>
                        <input type="text" name="name" id="name" required
                            class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>



                    <div class="mt-4 text-right">
                        <button
                            class="px-4 submit py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-400">Save</button>
                        <button type="button" data-modal-hide="add-modal"
                            class="ml-2 px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

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
                    text: 'Ship Added successfully!',
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
            });
    });
});

</script>
