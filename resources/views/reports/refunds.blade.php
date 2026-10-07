<x-app-layout>
    @vite(['resources/js/pages/refund-report.js'])
    <div class="py-6">
        <div class="mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Refunded Report</h2>
                <button id="clearRefundReportFilters" type="button"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Clear filters
                </button>
            </div>

            <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="refundReportShip" class="mb-1 block text-sm font-medium text-gray-700">Ship</label>
                    <select id="refundReportShip" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">All ships</option>
                        @foreach ($ships as $ship)
                            <option value="{{ $ship->id }}">{{ $ship->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="refundReportCompany" class="mb-1 block text-sm font-medium text-gray-700">Company</label>
                    <select id="refundReportCompany" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">All companies</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="refundReportType" class="mb-1 block text-sm font-medium text-gray-700">Refund type</label>
                    <select id="refundReportType" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="all">All types</option>
                        <option value="bulk">Bulk</option>
                        <option value="partial">Partial</option>
                        <option value="extra_payment">Extra payment</option>
                    </select>
                </div>
            </div>

            <x-data-table id="refundReportTable" :headings="[
                'Refund ID', 'Sale ID', 'Customer', 'Ship', 'Refunded At', 'Type', 'Tickets',
                'Gross Refund', 'Discount', 'Customer Charge', 'Partner Share',
                'Company Retained', 'Due Adjusted', 'Final Customer Refund', 'Customer Paid', 'Status', 'Details',
            ]" url="{{ route('refund-reports.data') }}" :ordering="false" :delegateActions="false" :order="[]"
                :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />

            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <x-report-summary-card id="refundReportCount" label="Refunded Transactions" />
                <x-report-summary-card id="refundReportTotal" label="Total Gross Refund Amount" />
                <x-report-summary-card id="refundReportDiscount" label="Refund Discount" />
                <x-report-summary-card id="refundReportDueAdjusted" label="Due Adjusted (Completed)" />
                <x-report-summary-card id="refundReportCompanyRetained" label="Company Retained" />
                <x-report-summary-card id="refundReportPaid" label="Customer Refund Paid" />
                <x-report-summary-card id="refundReportExtraPaid" label="Extra Payment Refund Paid" />
                <x-report-summary-card id="refundReportOutflow" label="Refund Outflow (Customer + Partner + Extra Refunds)" />
            </div>
        </div>
    </div>

    <x-entity-modal id="refundReportDetailModal" title="Refund Details" maxWidth="2xl" :hideFooter="true">
        <div id="refundReportDetails" class="space-y-5 p-5"></div>
    </x-entity-modal>
</x-app-layout>
