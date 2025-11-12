<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 ">
        <div id="statusFilter" data-status="shipped" class="hidden"></div>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Ship Ticket Sales
        </h2>
        <div class="mt-6 mb-6 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6">

            <!-- Ship Filter -->
            <div class="flex flex-col">
                <label for="shipFilter" class="text-sm font-semibold text-gray-700 mb-1">Filter by Ship</label>
                <select id="shipFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <!-- Options populated dynamically -->
                </select>
            </div>

            <!-- Company Filter -->
            <div class="flex flex-col">
                <label for="companyFilter" class="text-sm font-semibold text-gray-700 mb-1">Filter by Source
                    Company</label>
                <select id="companyFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <!-- Options populated dynamically -->
                </select>
            </div>

            <!-- Payment Method Filter -->
            <div class="flex flex-col">
                <label for="payment_method" class="text-sm font-semibold text-gray-700 mb-1">Payment Method</label>
                <select id="payment_method"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">All Methods</option>
                    <option value="Cash">Cash</option>
                    <option value="Bkash">Bkash</option>
                    <option value="Nagad">Nagad</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
            </div>

            <!-- Start & End Date -->
            <div class="flex flex-col md:flex-row gap-2">
                <div class="flex-1 flex flex-col">
                    <label for="startDate" class="text-sm font-semibold text-gray-700 mb-1">Start Date(Journey)</label>
                    <input type="date" id="startDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div class="flex-1 flex flex-col">
                    <label for="endDate" class="text-sm font-semibold text-gray-700 mb-1">End Date(Journey)</label>
                    <input type="date" id="endDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>

            <!-- Journey Date Filter -->
            <div class="flex flex-col">
                <label for="journeyDateFilter" class="text-sm font-semibold text-gray-700 mb-1">Journey Date</label>
                <input type="date" id="journeyDateFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>

            <div class="flex flex-col">
                <label for="returnDateFilter" class="text-sm font-semibold text-gray-700 mb-1">Return Date</label>
                <input type="date" id="returnDateFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>

            <div class="flex flex-col">
                <label for="createdDateFilter" class="text-sm font-semibold text-gray-700 mb-1">
                    Created Date
                </label>
                <input type="date" id="createdDateFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>

            <div class="flex flex-col md:flex-row gap-2">
                <div class="flex-1 flex flex-col">
                    <label for="startCreateDate" class="text-sm font-semibold text-gray-700 mb-1">Start Date(create)</label>
                    <input type="date" id="startCreateDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div class="flex-1 flex flex-col">
                    <label for="endCreateDate" class="text-sm font-semibold text-gray-700 mb-1">End Date(create)</label>
                    <input type="date" id="endCreateDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>

            <!-- Clear Filters Button -->
            <div class="flex items-end">
                <button id="clearFilters"
                    class="w-full md:w-auto px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
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
            <table id="salesTable" class="min-w-full border border-gray-300 hidden ">
                <thead class="bg-[#003366] text-white">
                    <tr>
                        <th class="border px-4 py-1">ID</th>
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
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Total Refunded Tickets -->
            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sell Tickets</p>
                    <p id="totalSellTickets" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
                <div class="text-blue-200 dark:text-blue-600 text-3xl">
                    <!-- Optional icon -->
                </div>
            </div>

            <!-- Total Refunded Amount -->
            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sell Amount</p>
                    <p id="totalSellAmount" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
                <div class="text-blue-200 dark:text-blue-600 text-3xl">
                    <!-- Optional icon -->
                </div>
            </div>
            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Refunded Tickets</p>
                    <p id="totalRefundedTickets" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
                <div class="text-blue-200 dark:text-blue-600 text-3xl">
                    <!-- Optional icon -->
                </div>
            </div>

            <!-- Total Refunded Amount -->
            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Refunded Amount</p>
                    <p id="totalRefundedAmount" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
                <div class="text-blue-200 dark:text-blue-600 text-3xl">
                    <!-- Optional icon -->
                </div>
            </div>
        </div>
    </div>
</div>


<script src="{{ asset('js/reports.js') }}"></script>
