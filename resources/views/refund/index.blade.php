<x-app-layout>
    @vite(['resources/js/pages/refunds.js'])
<div id="refundPage" data-can-manage="{{ auth()->user()?->can('refunds.create') ? '1' : '0' }}"
    data-full-refund-url="{{ route('refunds.full') }}"
    data-partial-refund-url="{{ route('refunds.partial', ['id' => '__ID__']) }}"
    data-update-refund-url="{{ route('refunds.update', ['refund' => '__ID__']) }}"
    data-sale-url="{{ route('ship-ticket-sales.show', ['ship_ticket_sale' => '__ID__']) }}" class="py-6">
    <div class=" mx-auto sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Refundable Sales
        </h2>

        <div class="mt-6 mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <label for="companyFilter" class="block text-sm font-medium text-gray-700">Filter by Source
                    Company</label>
                <select id="companyFilter"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Companies</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="shipFilter" class="block text-sm font-medium text-gray-700">Filter by Ship</label>
                <select id="shipFilter"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Ships</option>
                    @foreach ($ships as $ship)
                        <option value="{{ $ship->id }}">{{ $ship->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="packageFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Ticket Category</label>
                <select id="packageFilter" disabled
                    data-packages-url="{{ route('ship-packages.data', ['id' => '__SHIP_ID__']) }}"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100 sm:text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Select a ship first</option>
                </select>
                <p id="packageFilterMessage" role="status" class="mt-1 text-xs text-gray-500 dark:text-gray-400"></p>
            </div>
            <div class="flex-1">
                <label for="journeyDateFilter" class="block text-sm font-medium text-gray-700">Filter by Journey
                    Date</label>
                <input type="date" id="journeyDateFilter"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="flex-1">
                <label for="returnDateFilter" class="block text-sm font-medium text-gray-700">Filter by Return
                    Date</label>
                <input type="date" id="returnDateFilter"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div class="flex items-end">
                <button id="clearFilters"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Clear Filters
                </button>
            </div>
        </div>

        

        <x-data-table id="salesTable" :headings="[
            '',
            'ID',
            'Customer Name',
            'Ship Name',
            'Journey Date',
            'Number Of Ticket',
            'Total Ticket Price',
            'Other Fee',
            'Discount Amount',
            'Total Payable',
            'Total Received Amount',
            'Due Amount',
            'Action',
        ]" url="{{ route('refunds.refundable') }}" :ordering="false" :delegateActions="false" :order="[]"
            :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />

        <div class="flex justify-end mt-10">
            <button id="refundSelectedBtn" data-permission="refunds.create"
                class="px-4 py-2 bg-green-900 text-white rounded-md hover:bg-green-600 focus:outline-none">
                Refund Selected
            </button>
        </div>
    </div>
</div>
</x-app-layout>


@include('refund.refundModal')
