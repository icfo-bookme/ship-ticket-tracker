<x-app-layout>
    @vite(['resources/js/pages/ticket-issued-report.js'])
    <div class="py-6">
        <div class="mx-auto flex flex-col gap-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Ticket Issued Report</h2>
                <button id="clearTicketIssuedFilters" type="button"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Clear Filters
                </button>
            </div>
            <div id="ticketIssuedReportFilters" data-url="{{ route('ticket-issued-reports.data') }}"
                class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div class="flex flex-col gap-2">
                    <label for="ticketIssuedFrom" class="text-sm font-medium text-gray-700">Issued From</label>
                    <input id="ticketIssuedFrom" name="ticket_issued_from" type="date" value="{{ $today }}"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex flex-col gap-2">
                    <label for="ticketIssuedTo" class="text-sm font-medium text-gray-700">Issued To</label>
                    <input id="ticketIssuedTo" name="ticket_issued_to" type="date" value="{{ $today }}" min="{{ $today }}"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <x-searchable-multi-select id="ticketIssuedShips" name="ship_ids[]" label="Ships" :options="$ships"
                    help="Select multiple ships. No selection means All Ships." />
                <div class="flex flex-col gap-2">
                    <label for="ticketIssuedDateOrder" class="text-sm font-medium text-gray-700">Issued Date Order</label>
                    <select id="ticketIssuedDateOrder" name="issued_date_order"
                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="desc">Newest First (DESC)</option>
                        <option value="asc">Oldest First (ASC)</option>
                    </select>
                </div>
            </div>

            <p class="text-xs text-gray-500">Issue dates follow ticket issue history in Bangladesh time. Financial amounts show each sale's current amounts.</p>
            <div id="ticketIssuedReportConfig"
                data-can-edit="{{ auth()->user()->can('sales.edit') ? 'true' : 'false' }}"
                data-edit-url="{{ route('ship-ticket-sales.show', ['ship_ticket_sale' => '__ID__']) }}"></div>
            <x-data-table id="ticketIssuedTable" :headings="[
                'Sale ID', 'Customer', 'Mobile', 'Ship', 'Source Company', 'Journey Date',
                'Tickets', 'Issued At', 'Issued By', 'Current Status', 'Ticket Value',
                'Total Payable', 'Total Received', 'Current Due', 'Action',
            ]" :url="route('ticket-issued-reports.data')" :ordering="false" :delegateActions="false" :order="[]"
                :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />

            <section id="ticketIssuedSummary" aria-labelledby="ticketIssuedSummaryHeading">
                <h3 id="ticketIssuedSummaryHeading" class="mb-3 text-base font-semibold text-gray-800">Filtered Summary</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <x-report-summary-card id="ticketIssuedTotalSales" label="Total Sales" />
                    <x-report-summary-card id="ticketIssuedTotalTickets" label="Total Tickets" />
                    <x-report-summary-card id="ticketIssuedTotalTicketValue" label="Ticket Value" :blue="true" />
                    <x-report-summary-card id="ticketIssuedTotalDiscount" label="Sales Discount" />
                    <x-report-summary-card id="ticketIssuedTotalOtherFees" label="Other Fees" />
                    <x-report-summary-card id="ticketIssuedTotalPayable" label="Total Payable" />
                    <x-report-summary-card id="ticketIssuedTotalReceived" label="Total Received" />
                    <x-report-summary-card id="ticketIssuedTotalDue" label="Current Due" />
                </div>
                <p class="mt-3 text-xs text-gray-500">Totals include all matching sales across every page. Received and due amounts reflect the sales' current financial position.</p>
            </section>

            <x-entity-modal id="ticketIssuedDetailModal" title="Sale Details" maxWidth="4xl" :hideFooter="true">
                <div class="flex flex-col gap-5 p-5">
                    <dl id="ticketIssuedSaleDetails" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"></dl>
                    <section>
                        <h4 class="mb-3 text-xs font-medium uppercase text-gray-500">Verification Timeline</h4>
                        <div id="ticketIssuedTimeline" class="grid grid-cols-1 gap-3 sm:grid-cols-2"></div>
                    </section>
                </div>
            </x-entity-modal>
        </div>
    </div>
</x-app-layout>
