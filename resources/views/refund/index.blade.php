<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 ">
        <div id="statusFilter" data-status="shipped" class="hidden"></div>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Ship Ticket Sales
        </h2>
        <div class="mt-6 mb-4 grid grid-cols-3 gap-10">
            <div class="">
                <label for="companyFilter" class="block text-sm font-medium text-gray-700">Filter by Source
                    Company</label>
                <select id="companyFilter"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <!-- Options will be populated dynamically via JS -->
                </select>
            </div>
            <div class="">
                <label for="shipFilter" class="block text-sm font-medium text-gray-700">Filter by Ship</label>
                <select id="shipFilter"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <!-- Options will be populated dynamically via JS -->
                </select>
            </div>
            <div class="flex-1">
                <label for="journeyDateFilter" class="block text-sm font-medium text-gray-700">Filter by Journey
                    Date</label>
                <input type="date" id="journeyDateFilter"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div class="flex items-end">
                <button id="clearFilters"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- Loader -->
        <div id="loader" class="text-center my-4">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Loading data...</p>
        </div>

        <!-- Sales Table -->
        <div class="overflow-x-auto">
            <table id="salesTable" class="min-w-full border border-gray-300 hidden">
                <thead class="bg-[#003366] text-white">
                    <tr>
                        <th class="border px-4 py-2">
                            <input type="checkbox" id="selectAll" class="form-checkbox" />
                        </th>
                        <th class="border px-4 py-2">ID</th>
                        <th class="border px-4 py-2">Customer Name</th>
                        <th class="border px-4 py-2">Mobile</th>
                        <th class="border px-4 py-2">Ship Name</th>
                        <th class="border px-4 py-2">Journey Date</th>
                        <th class="border px-4 py-2">Number Of Ticket</th>
                        <th class="border px-4 py-2">Total Ticket Price</th>
                        <th class="border px-4 py-2">Received Amount</th>
                        <th class="border px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody id="salesBody"></tbody>
            </table>
            <div class="flex justify-end mt-10">
                <button id="refundSelectedBtn"
                    class="px-4 py-2 bg-green-900 text-white rounded-md hover:bg-green-600 focus:outline-none">
                    Refund Selected
                </button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/refund.js') }}"></script>
