<x-app-layout>
    @vite(['resources/js/pages/bftn-report.js'])
    <div class="py-6">
        <div class="mx-auto space-y-6 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">BFTN Report</h2>
                <button id="clearBftnFilters" type="button" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Clear filters</button>
            </div>

            <div class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label for="bftnReportStatus" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                    <select id="bftnReportStatus" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="all">All BFTN</option>
                        <option value="pending">Pending</option>
                        <option value="received">Received</option>
                    </select>
                </div>
                <div>
                    <label for="bftnReportShip" class="mb-1 block text-sm font-medium text-gray-700">Ship</label>
                    <select id="bftnReportShip" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">All ships</option>
                        @foreach ($ships as $ship)
                            <option value="{{ $ship->id }}">{{ $ship->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="bftnReportCompany" class="mb-1 block text-sm font-medium text-gray-700">Company</label>
                    <select id="bftnReportCompany" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="">All companies</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="bftnReportStartDate" class="mb-1 block text-sm font-medium text-gray-700">BFTN date from</label>
                    <input type="date" id="bftnReportStartDate" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label for="bftnReportEndDate" class="mb-1 block text-sm font-medium text-gray-700">BFTN date to</label>
                    <input type="date" id="bftnReportEndDate" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label for="bftnReportReceivedDate" class="mb-1 block text-sm font-medium text-gray-700">Received date</label>
                    <input type="date" id="bftnReportReceivedDate" class="w-full rounded-md border-gray-300 text-sm">
                </div>
            </div>

            <div id="bftnReportConfig" data-can-receive="{{ auth()->user()->can('sales.bftn.receive') ? 'true' : 'false' }}"></div>
            <x-data-table id="bftnReportTable" :headings="[
                'Sale ID', 'Customer', 'Mobile', 'Ship', 'Company', 'Amount', 'BFTN Date', 'Status', 'Received At', 'Action',
            ]" url="{{ route('bftn-reports.data') }}" :ordering="false" :delegateActions="false" :order="[]"
                :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />

            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <x-report-summary-card id="bftnReportTotalCount" label="BFTN Sales" />
                <x-report-summary-card id="bftnReportTotalAmount" label="BFTN Amount" />
                <x-report-summary-card id="bftnReportPending" label="Pending Amount" />
                <x-report-summary-card id="bftnReportReceived" label="Received Amount" />
                <x-report-summary-card id="bftnReportPendingCount" label="Pending Transfers" />
                <x-report-summary-card id="bftnReportReceivedCount" label="Received Transfers" />
            </div>
        </div>
    </div>

    <x-entity-modal id="bftnReceivedModal" title="Confirm BFTN Received" formId="bftnReceivedForm" submitText="Confirm" maxWidth="md">
        <form id="bftnReceivedForm" class="space-y-4 p-5">
            <input type="hidden" id="bftnReceivedSaleId">
            <div>
                <label for="bftnReceivedAt" class="mb-1 block text-sm font-medium text-gray-700">Received date and time</label>
                <input type="datetime-local" id="bftnReceivedAt" required class="w-full rounded-md border-gray-300 text-sm">
            </div>
        </form>
    </x-entity-modal>
</x-app-layout>
