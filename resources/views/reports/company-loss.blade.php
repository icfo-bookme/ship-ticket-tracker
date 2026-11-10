<x-app-layout>
    @vite(['resources/js/pages/company-loss-report.js'])
    <div class="py-6">
        <div class="mx-auto flex flex-col gap-6 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">Company Loss Report</h2>
                <button id="clearCompanyLossFilters" type="button" class="rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:text-gray-100">Clear Filters</button>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300">Company loss is included within the refund value. It is not an additional customer payment. Pending losses remain provisional until the refund is completed.</p>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach (['Ship' => $ships, 'Company' => $companies] as $label => $options)
                    <div>
                        <label for="companyLoss{{ $label }}" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
                        <select id="companyLoss{{ $label }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">All {{ $label === 'Company' ? 'companies' : 'ships' }}</option>
                            @foreach ($options as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div>
                    <label for="companyLossStatus" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Refund Status</label>
                    <select id="companyLossStatus" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">All active statuses</option>
                        @foreach (['requested' => 'Requested', 'partner_approved' => 'Partner Approved', 'payment_details_added' => 'Payment Details Added', 'completed' => 'Completed'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="companyLossType" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Loss Type</label>
                    <select id="companyLossType" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">All loss types</option>
                        <option value="partner_share">Partner Share Loss</option>
                        <option value="custom">Custom Loss</option>
                    </select>
                </div>
                @foreach (['From', 'To'] as $direction)
                    <div>
                        <label for="companyLoss{{ $direction }}" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Requested {{ $direction }}</label>
                        <input type="date" id="companyLoss{{ $direction }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                @endforeach
            </div>
            <x-data-table id="companyLossTable" :headings="[
                'Refund ID', 'Sale ID', 'Customer', 'Ship', 'Company', 'Requested At',
                'Status', 'Gross Refund', 'Loss Type', 'Loss %', 'Company Loss', 'Reason', 'Created By',
            ]" :url="route('company-loss-reports.data')" :ordering="false" :delegateActions="false" :order="[]"
                :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-report-summary-card id="companyLossCount" label="Matching Refunds" />
                <x-report-summary-card id="companyLossTotal" label="Total Recorded Company Loss" />
                <x-report-summary-card id="companyLossPending" label="Pending Company Loss" />
                <x-report-summary-card id="companyLossCompleted" label="Completed Company Loss" />
            </div>
        </div>
    </div>
</x-app-layout>
