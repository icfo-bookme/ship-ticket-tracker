<x-app-layout>


    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                Pending Ship Ticket Sales
            </h2>
            <!-- Loader -->
           <div id="loader" class="text-center my-4">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <p class="mt-2 text-gray-600">Loading data...</p>
            </div>

            <!-- Sales Table -->
            <div class="overflow-x-auto">
                <table id="salesTable" class="min-w-full border border-gray-300 hidden">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-4 py-2">ID</th>
                            <th class="border px-4 py-2">Customer Name</th>
                            <th class="border px-4 py-2">Mobile</th>
                            <th class="border px-4 py-2">Ship Name</th>
                            <th class="border px-4 py-2">Journey Date</th>
                            <th class="border px-4 py-2">Ticket Fee</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody id="salesBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg w-96">
            <h2 class="text-xl font-bold mb-4">Edit Sale</h2>
            <form id="editForm">
                <input type="hidden" id="editId">

                <div class="mb-4">
                    <label for="editCustomerName" class="block text-sm font-medium">Customer Name</label>
                    <input type="text" id="editCustomerName" class="w-full border px-3 py-2 rounded-lg">
                </div>

                <div class="mb-4">
                    <label for="editMobile" class="block text-sm font-medium">Customer Mobile</label>
                    <input type="text" id="editMobile" class="w-full border px-3 py-2 rounded-lg">
                </div>

                <div class="mb-4">
                    <label for="editStatus" class="block text-sm font-medium">Status</label>
                    <select id="editStatus" class="w-full border px-3 py-2 rounded-lg">
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="flex justify-end">
                    <button type="button" id="closeModal" class="bg-gray-300 px-4 py-2 rounded mr-2">Cancel</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Save</button>
                </div>
            </form>
        </div>
    </div>

<script src="{{ asset('js/panding-sell.js') }}"></script>
   

</x-app-layout>
