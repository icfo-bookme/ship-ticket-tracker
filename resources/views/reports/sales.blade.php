<div class="py-6">
    <div class=" mx-auto sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 max-w-[92%]">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Sales Reports
            </h2>
            <div class="flex items-end">
                <button id="clearFilters"
                    class="w-full md:w-auto px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    Clear Filters
                </button>
            </div>
        </div>

        <div class="mt-6 mb-6 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6 max-w-[92%]">
            <div class="flex flex-col">
                <label for="shipFilter" class="text-sm font-semibold text-gray-700 mb-1">Ship</label>
                <select id="shipFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">All Ships</option>
                    @foreach ($ships as $ship)
                        <option value="{{ $ship->id }}">{{ $ship->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col">
                <label for="companyFilter" class="text-sm font-semibold text-gray-700 mb-1">Source
                    Company</label>
                <select id="companyFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">All Companies</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

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

            <div class="flex flex-col">
                <label for="bftnFilter" class="text-sm font-semibold text-gray-700 mb-1">BFTN Status</label>
                <select id="bftnFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">All</option>
                    <option value="all">All BFTN</option>
                    <option value="pending">BFTN Pending</option>
                    <option value="received">BFTN Received</option>
                </select>
            </div>

            <div class="flex flex-col md:flex-row gap-2">
                <div class="flex-1 flex flex-col">
                    <label for="startDate" class="text-sm font-semibold text-gray-700 mb-1">Journey Date
                        From</label>
                    <input type="date" id="startDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div class="flex-1 flex flex-col">
                    <label for="endDate" class="text-sm font-semibold text-gray-700 mb-1">To</label>
                    <input type="date" id="endDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>

            <div class="flex flex-col">
                <label for="returnDateFilter" class="text-sm font-semibold text-gray-700 mb-1">Return Date</label>
                <input type="date" id="returnDateFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>

            <div class="flex flex-col md:flex-row gap-2">
                <div class="flex-1 flex flex-col">
                    <label for="startCreateDate" class="text-sm font-semibold text-gray-700 mb-1">Created Date:
                        From</label>
                    <input type="date" id="startCreateDate" value="{{ now()->subDays(6)->toDateString() }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div class="flex-1 flex flex-col">
                    <label for="endCreateDate" class="text-sm font-semibold text-gray-700 mb-1">To</label>
                    <input type="date" id="endCreateDate" value="{{ now()->toDateString() }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>
        </div>

        

        <div id="salesReportConfig" data-can-edit="{{ auth()->user()->can('sales.edit') ? 'true' : 'false' }}"></div>
        <x-data-table id="salesTable" :headings="[
            'ID',
            'Customer Name',
            'Mobile',
            'Ship Name',
            'Journey Date',
            'Number Of Ticket',
            'Total Ticket Price',
            'Other Fee',
            'Discount Amount',
            'Total Payable',
            'Received Amount',
            'Extra Received',
            'Extra Refunded',
            'Refunded Tickets',
            'Refunded Amount',
            'Gross Amount',
            'Final Customer Refund',
            'Due Adjusted',
            'Partner Share',
            'Company Retained',
            'BFTN',
            'BFTN Received',
            'BFTN Amount',
            'Net Cash',
            'Due Amount',
            'Action',
        ]" url="/reports" :ordering="false" :delegateActions="false" :order="[]"
            :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />

        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-6 max-w-[92%]">
            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Number of Sold Tickets</p>
                    <p id="totalSellTickets" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sold Tickets Value/Fees</p>
                    <p id="totalSoldTicketsAmount" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Other Fees Collections</p>
                    <p id="totalOtherFees" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Discount Amount</p>
                    <p id="totalDiscountAmount" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-green-500 dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-white dark:text-gray-400">Total Payable</p>
                    <p id="totalSold" class="text-2xl font-bold text-white dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-red-500 dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-white dark:text-gray-400">Total Refunded Tickets</p>
                    <p id="totalRefundedTickets" class="text-2xl text-white font-bold dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-red-500 dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-white dark:text-gray-400">Total Refunded Amount</p>
                    <p id="totalRefundedAmount" class="text-2xl font-bold text-white dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-indigo-500 dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-white dark:text-gray-400">Total Received Amount</p>
                    <p id="totalReceivedAmount" class="text-2xl font-bold text-white dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-yellow-600 dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-white dark:text-gray-400">Total Due Amount</p>
                    <p id="totalDueAmount" class="text-2xl font-bold text-white dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-teal-600 dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-white dark:text-gray-400">Net Sales Amount</p>
                    <p id="netSalesAmount" class="text-2xl font-bold text-white dark:text-blue-400">0</p>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gross Amount</p>
                <p id="totalGrossRefundAmount" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0.00</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Final Customer Refund</p>
                <p id="totalCustomerRefundAmount" class="text-2xl font-bold text-green-700 dark:text-green-400">0.00</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Due Adjusted</p>
                <p id="totalDueAdjustedAmount" class="text-2xl font-bold text-red-700 dark:text-red-400">0.00</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer Refund After Due Adjustment</p>
                <p id="totalCustomerRefundAfterDueAdjustment" class="text-2xl font-bold text-red-700 dark:text-red-400">0.00</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Partner Share</p>
                <p id="totalPartnerShareAmount" class="text-2xl font-bold text-amber-700 dark:text-amber-400">0.00</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Company Retained</p>
                <p id="totalCompanyRetainedAmount" class="text-2xl font-bold text-purple-700 dark:text-purple-400">0.00</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total BFTN</p>
                <p id="totalBftn" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">BFTN Pending</p>
                <p id="totalBftnPending" class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">0</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">BFTN Received</p>
                <p id="totalBftnReceived" class="text-2xl font-bold text-green-700 dark:text-green-400">0</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Cash</p>
                <p id="netCash" class="text-2xl font-bold text-teal-700 dark:text-teal-400">0.00</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total BFTN Amount</p>
                <p id="totalBftnAmount" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0.00</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">BFTN Pending Amount</p>
                <p id="totalBftnPendingAmount" class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">0.00</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">BFTN Received Amount</p>
                <p id="totalBftnReceivedAmount" class="text-2xl font-bold text-green-700 dark:text-green-400">0.00</p>
            </div>
        </div>
    </div>
</div>


