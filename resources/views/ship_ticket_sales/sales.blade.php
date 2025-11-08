<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 ">
        <div id="statusFilter" data-status="{{ $status }}" class="hidden"></div>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
            Ship Ticket Sales ({{ $status }} )
        </h2>
        <div class="mt-6 mb-4 grid grid-cols-3 gap-10">
            <div class="">
                <label for="shipFilter" class="block text-sm font-medium text-gray-700">Filter by Ship</label>
                <select id="shipFilter"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <!-- Options will be populated dynamically via JS -->
                </select>
            </div>

            <div class="">
                <label for="companyFilter" class="block text-sm font-medium text-gray-700">Filter by Source
                    Company</label>
                <select id="companyFilter"
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
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-4 py-2">ID</th>
                        <th class="border px-4 py-2">Customer Name</th>
                        <th class="border px-4 py-2">Mobile</th>
                        <th class="border px-4 py-2">Ship Name</th>
                        <th class="border px-4 py-2">Journey Date</th>
                        <th class="border px-4 py-2">Ticket Fee</th>
                        <th class="border px-4 py-2">Resource Company</th>
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
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div
        class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white h-full max-h-[90%] py-5 overflow-y-auto">
        <div class="mt-3 h-full">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-xl font-semibold text-gray-900">Edit Sale</h3>
                <button id="closeModalX" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="editForm" class="mt-4">
                <input type="hidden" id="editId">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Customer Information -->
                    <div class="mb-4">
                        <label for="editCustomerName" class="block text-sm font-medium text-gray-700">Customer
                            Name</label>
                        <input type="text" id="editCustomerName"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>

                    <div class="mb-4">
                        <label for="editMobile" class="block text-sm font-medium text-gray-700">Customer Mobile</label>
                        <input type="text" id="editMobile"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>

                    <div class="mb-4">
                        <label for="editnid" class="block text-sm font-medium text-gray-700">NID</label>
                        <input type="text" id="editnid"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>

                    <div class="mb-4">
                        <label for="editEmail" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="text" id="editEmail"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>

                    <!-- Sales Source (Changed to dropdown) -->
                    <div class="mb-4">
                        <label for="editSalesSource" class="block text-sm font-medium text-gray-700">Sales
                            Source</label>
                        <select id="editSalesSource"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                            <option value="">Select source</option>

                            <option value="WhatsApp(016)">WhatsApp(016)</option>
                            <option value="WhatsApp(018)">WhatsApp(018)</option>
                            <option value="WhatsApp(019)">WhatsApp(019)</option>
                            <option value="Facebook">Facebook</option>
                            <option value="Messenger">Messenger</option>
                            <option value="Walk-in">Walk-in</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>

                    <!-- Ship Selection -->
                    <div class="mb-4">
                        <label for="editShip" class="block text-sm font-medium text-gray-700">Ship</label>
                        <select id="editShip"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                            <option value="">Select Ship</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>

                    <!-- Journey Date -->
                    <div class="mb-4">
                        <label for="editJourneyDate" class="block text-sm font-medium text-gray-700">Journey
                            Date</label>
                        <input type="date" id="editJourneyDate"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>

                    <div class="mb-4">
                        <label for="editReturnDate" class="block text-sm font-medium text-gray-700">Return
                            Date</label>
                        <input type="date" id="editReturnDate"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>

                    <!-- Ticket Fee -->
                    <div class="mb-4">
                        <label for="editTicketFee" class="block text-sm font-medium text-gray-700">Ticket Fee</label>
                        <input type="number" step="0.01" id="editTicketFee"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>

                    <div class="mb-4">
                        <label for="editTicketNumber" class="block text-sm font-medium text-gray-700">Number Of
                            Tickets</label>
                        <input type="number" step="1" id="editTicketNumber"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>

                    <!-- Payment Information -->
                    <div class="mb-4">
                        <label for="editPaymentMethod" class="block text-sm font-medium text-gray-700">Payment
                            Method</label>
                        <select id="editPaymentMethod"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                            <option value="Cash">Cash</option>
                            <option value="Bkash">Bkash</option>
                            <option value="Nagad">Nagad</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="editReceivedAmount" class="block text-sm font-medium text-gray-700">Received
                            Amount</label>
                        <input type="number" step="0.01" id="editReceivedAmount"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>

                    <div class="mb-4">
                        <label for="editDueAmount" class="block text-sm font-medium text-gray-700">Due Amount</label>
                        <input type="number" step="0.01" id="editDueAmount"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>

                    <!-- Company -->
                    <div class="mb-4">
                        <label for="editCompany" class="block text-sm font-medium text-gray-700">Company</label>
                        <select id="editCompany"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                            <option value="">Select Company</option>
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>

                    <!-- Issued Date -->
                    <div class="mb-4">
                        <label for="editIssuedDate" class="block text-sm font-medium text-gray-700">Issued
                            Date</label>
                        <input type="date" id="editIssuedDate"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>

                    <!-- Sold By -->
                    <div class="mb-4">
                        <label for="editSoldBy" class="block text-sm font-medium text-gray-700">Sold By</label>
                        <input type="text" id="editSoldBy"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <label for="editStatus" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="editStatus"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                            <option value="pending">Pending</option>
                            <option value="payment-verified">payment-verified</option>
                            <option value="ticket-issued">ticket-issued</option>
                            <option value="ticket-printed">ticket-printed</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="editTicketCategory" class="block text-sm font-medium text-gray-700">Ticket
                            Category</label>
                        <input type="text" id="editTicketCategory"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                    </div>
                </div>

                <div class="flex justify-end py-5 space-x-3">
                    <button type="button" id="cancelBtn"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-950 text-white rounded-md hover:bg-blue-800">
                        Update Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>




<script src="{{ asset('js/panding-sell.js') }}"></script>
