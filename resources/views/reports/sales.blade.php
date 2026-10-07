<div class="py-6">
    <div class=" mx-auto sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 max-w-[92%]">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Sales Reports
            </h2>
            <div class="flex items-center gap-2">
                <button id="toggleAdvancedFilters" type="button" aria-expanded="false" aria-controls="advancedReportFilters"
                    class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-[12px] font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-sliders-h text-xs" aria-hidden="true"></i>
                    <span>More filters</span>
                </button>
                <button id="clearFilters"
                    class="px-3 py-2 bg-blue-200 text-gray-800 text-[12px] font-semibold rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    Clear Filters
                </button>
            </div>
        </div>

        <div class="mt-6 mb-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4 max-w-[92%] justify-center items-end">
            <div class="flex flex-col">
                <label for="shipFilter" class="text-[12px] font-semibold text-gray-700 mb-1">Ship</label>
                <select id="shipFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-[12px]">
                    <option value="">All Ships</option>
                    @foreach ($ships as $ship)
                        <option value="{{ $ship->id }}">{{ $ship->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col">
                <label for="companyFilter" class="text-[12px] font-semibold text-gray-700 mb-1">Source
                    Company</label>
                <select id="companyFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-[12px]">
                    <option value="">All Companies</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col">
                <span class="text-[12px] font-semibold text-gray-700 mb-1">Journey Date</span>
                <div class="grid grid-cols-2 gap-2">
                <div class="flex-1 flex flex-col">
                    <label for="startDate" class="text-xs text-gray-600 mb-1">From</label>
                    <input type="date" id="startDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-[12px]">
                </div>
                <div class="flex-1 flex flex-col">
                    <label for="endDate" class="text-xs text-gray-600 mb-1">To</label>
                    <input type="date" id="endDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-[12px]">
                </div>
                </div>
            </div>

            <div class="flex flex-col">
                <span class="text-[12px] font-semibold text-gray-700 mb-1">Created Date</span>
                <div class="grid grid-cols-2 gap-2">
                <div class="flex-1 flex flex-col">
                    <label for="startCreateDate" class="text-xs text-gray-600 mb-1">From</label>
                    <input type="date" id="startCreateDate" value="{{ now()->subDays(6)->toDateString() }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-[12px]">
                </div>
                <div class="flex-1 flex flex-col">
                    <label for="endCreateDate" class="text-xs text-gray-600 mb-1">To</label>
                    <input type="date" id="endCreateDate" value="{{ now()->toDateString() }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-[12px]">
                </div>
                </div>
            </div>
        </div>

        <div id="advancedReportFilters" hidden class="mb-6 max-w-[92%]">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="flex flex-col">
                    <label for="returnDateFilter" class="mb-1 text-[12px] font-semibold text-gray-700">Return Date</label>
                    <input type="date" id="returnDateFilter"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-[12px] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex flex-col">
                    <label for="payment_method" class="mb-1 text-[12px] font-semibold text-gray-700">Payment Method</label>
                    <select id="payment_method"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-[12px] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Methods</option>
                        <option value="Cash">Cash</option>
                        <option value="Bkash">Bkash</option>
                        <option value="Nagad">Nagad</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                    </select>
                </div>
            </div>
        </div>

        

        <div id="salesReportConfig" data-can-edit="{{ auth()->user()->can('sales.edit') ? 'true' : 'false' }}"></div>
        <x-data-table id="salesTable" :headings="[
            'ID',
            'Customer Name',
            'Mobile',
            'Ship Name',
            'Number Of Tickets',
            'Ticket Price / Other Fee / Discount',
            'Total Payable',
            'Received Amount',
            'BFTN',
            'Net Cash',
            'Due Amount',
            'Action',
        ]" url="/reports" :ordering="false" :delegateActions="false" :pageLength="10" :order="[]"
            :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />

        <x-entity-modal id="saleReportDetailModal" title="Sale Details" maxWidth="2xl" :hideFooter="true">
            <dl id="saleReportDetails" class="grid grid-cols-1 gap-x-8 gap-y-4 p-5 sm:grid-cols-2 lg:grid-cols-3"></dl>
        </x-entity-modal>

        <div class="mt-6 grid grid-cols-1 gap-6 max-w-[92%]">
            <section>
                <h3 class="mb-3 text-base font-semibold text-gray-800">Sales</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-report-summary-card id="totalSellTickets" label="Tickets Sold" />
                    <x-report-summary-card id="totalSoldTicketsAmount" label="Ticket Value" />
                    <x-report-summary-card id="totalDiscountAmount" label="Sales Discount" />
                    <x-report-summary-card id="ticketSalesAfterDiscount" label="Ticket Sales After Discount" />
                    <x-report-summary-card id="totalOtherFees" label="Other Fees Collected" />
                    <x-report-summary-card id="totalSold" label="Total Payable" />
                    <x-report-summary-card id="totalExtraReceivedAmount" label="Extra Payment Received (Included in Total Received)" />
                    <x-report-summary-card id="totalDueAmount" label="Current Due" />
                    <x-report-summary-card id="netSalesAmount" label="Sales After Ticket Refunds" />
                </div>
            </section>

            <section>
                <h3 class="mb-3 text-base font-semibold text-gray-800">Refunds &amp; Adjustments</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-report-summary-card id="totalGrossTicketRefundAmount" label="Gross Ticket Refund" />
                    <x-report-summary-card id="totalRefundDiscountAmount" label="Refund Discount" />
                    <x-report-summary-card id="totalCompletedTicketRefundAmount" label="Net Ticket Refund" />
                    <x-report-summary-card id="totalCustomerRefundPaid" label="Customer Refund Paid" />
                    <x-report-summary-card id="totalPartnerShareAmount" label="Partner Share" />
                    <x-report-summary-card id="totalCompanyRetainedAmount" label="Company Retained" />
                    <x-report-summary-card id="totalDueAdjustedAmount" label="Due Adjusted (Non-cash)" />
                    <x-report-summary-card id="totalExtraRefundedAmount" label="Extra Payment Refund Paid" />
                </div>
            </section>

            <section>
                <h3 class="mb-3 text-base font-semibold text-gray-800">BFTN Settlement</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-report-summary-card id="totalBftnPendingAmount" label="BFTN Pending Amount (Filtered)" />
                    <x-report-summary-card id="totalBftnReceivedAmount" label="BFTN Received Amount (Filtered)" />
                </div>
            </section>

            <section>
                <h3 class="mb-3 text-base font-semibold text-gray-800">Filtered Cash Summary</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-report-summary-card id="totalReceivedAmount" label="Total Received (BFTN Included)" />
                    <x-report-summary-card id="reportRefundOutflow" label="Refund Outflow (Customer + Partner + Extra Refunds)" />
                    <x-report-summary-card id="reportNetBeforeCashout" label="Net Receipts Before Cashout" :emphasis="true" />
                </div>
            </section>

            <details id="paymentMethodBreakdown" class="rounded-md border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <summary class="cursor-pointer px-4 py-3 text-[12px] font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-gray-100">
                    Payment Method Breakdown
                </summary>
                <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                    <div id="paymentMethodBreakdownCards" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"></div>
                    <p id="paymentMethodBreakdownEmpty" class="hidden text-[12px] text-gray-500">No payment records for the selected report filters.</p>
                </div>
            </details>
        </div>
    </div>
</div>

