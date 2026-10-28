@php
    $pendingStatus = \App\Enums\SaleStatus::Pending->value;
    $paymentVerifiedStatus = \App\Enums\SaleStatus::PaymentVerified->value;
    $ticketIssueRouteTemplate = route('ship-ticket-issue.show', ['ship_ticket_sale' => '__SALE_ID__']);
    $ticketIssuedStatus = \App\Enums\SaleStatus::TicketIssued->value;
    $ticketPrintedStatus = \App\Enums\SaleStatus::TicketPrinted->value;
    $shipmentIdEnteredStatus = \App\Enums\SaleStatus::ShipmentIdEntered->value;
    $shippedStatus = \App\Enums\SaleStatus::Shipped->value;
    $collectFromOfficeStatus = \App\Enums\SaleStatus::CollectFromOffice->value;
@endphp

<div class="w-full px-2 sm:px-4 lg:px-6">
    <div class="flex items-center justify-between py-6">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ship Ticket Sales ({{ $status }} )
        </h2>
    </div>
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="mt-6 mb-4 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-6">
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
            <label for="packageFilter" class="block text-sm font-medium text-gray-700">Filter by Package</label>
            <select id="packageFilter" disabled
                data-packages-url="{{ route('ship-packages.data', ['id' => '__SHIP_ID__']) }}"
                class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100 sm:text-sm">
                <option value="">Select a ship first</option>
            </select>
            <p id="packageFilterMessage" role="status" class="mt-1 text-xs text-gray-500"></p>
        </div>

        <div class="flex-1">
            <label for="journeyDateFilter" class="block text-sm font-medium text-gray-700">Filter by Departure
                Date</label>
            <input type="date" id="journeyDateFilter"
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        <div>
            <label for="sortFilter" class="block text-sm font-medium text-gray-700">Sort by</label>
            <select id="sortFilter"
                class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option value="latest">Latest</option>
                <option value="oldest">Oldest</option>
            </select>
        </div>

        <div class="flex items-end">
            <button id="clearFilters"
                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                Clear Filters
            </button>
        </div>
    </div>
    @if ($status === $shipmentIdEnteredStatus)
        <div id="bulkShippedToolbar"
            class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-md border border-blue-200 bg-blue-50 px-4 py-3">
            <span id="bulkShippedSelectionCount" class="text-sm font-medium text-blue-900">0 selected</span>
            <div class="flex flex-wrap items-center gap-2">
                <button id="bulkPrintShipmentLabelsButton" type="button" data-permission="sales.print" disabled
                    class="rounded-md border border-blue-300 bg-white px-3 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-50">
                    Print Selected
                </button>
                <button id="clearShippedSelection" type="button" data-permission="sales.mark_shipped"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                    Clear selection
                </button>
                <button id="bulkMarkShippedButton" type="button" data-permission="sales.mark_shipped" disabled
                    class="rounded-md bg-blue-700 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50">
                    Mark as Shipped
                </button>
            </div>
        </div>
    @endif

    <x-data-table id="salesTable" :headings="array_values(
        array_filter([
            $status == $shipmentIdEnteredStatus ? 'Select' : null,
            'ID',
            'Customer Name',
            'Mobile',
            'WhatsApp',
            'Ship Name',
            $status == $ticketPrintedStatus ? 'Address' : null,
            $status == $paymentVerifiedStatus ? 'Company' : null,
            in_array($status, [$shipmentIdEnteredStatus, $shippedStatus], true) ? 'Shipment Id' : null,
            $status == $pendingStatus ? 'Total Ticket Value' : null,
            $status == $pendingStatus ? 'Other Fee' : null,
            $status == $pendingStatus ? 'Discount Amount' : null,
            $status == $pendingStatus ? 'Total Payable' : null,
            $status == $pendingStatus ? 'Received Amount' : null,
            $status == $pendingStatus ? 'Transaction ID' : null,
            $status == $pendingStatus ? 'Payment Methods' : null,
            $status == $pendingStatus ? 'Payment Proof' : null,
            'Action',
        ]),
    )" :url="'/sales/' . $status" :ordering="false" :delegateActions="false"
        :order="[]" :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />

    <div id="saleStatusConfig" data-status="{{ $status }}" data-ticket-printed-status="{{ $ticketPrintedStatus }}"
        data-payment-verified-status="{{ $paymentVerifiedStatus }}"
        data-shipment-id-entered-status="{{ $shipmentIdEnteredStatus }}" data-pending-status="{{ $pendingStatus }}"
        data-collect-from-office-status="{{ $collectFromOfficeStatus }}"
        data-ticket-issued-status="{{ $ticketIssuedStatus }}" data-shipped-status="{{ $shippedStatus }}"
        data-status-labels='@json(config('sales.statuses'))' data-ticket-issue-url="{{ $ticketIssueRouteTemplate }}"
        data-destroy-url="{{ route('sale.destroy', ['id' => '__ID__']) }}"
        data-restore-url="{{ route('ship-ticket-sales.restore', ['id' => '__ID__']) }}"
        data-verify-url="{{ route('sale.verify', ['id' => '__ID__', 'status' => '__STATUS__']) }}"
        data-bftn-received-url="{{ route('payment.bftn-received', ['id' => '__ID__']) }}"
        data-bulk-shipped-url="{{ route('sales.bulk-shipped') }}"
    data-bulk-print-shipment-labels-url="{{ route('sales.bulk-shipment-labels') }}"
        data-select-shipped-url="{{ route('sales.select-shipped') }}"
        data-can-collect-due="{{ auth()->user()->can('payments.due.collect') ? 'true' : 'false' }}"></div>

    @vite(['resources/js/pages/sale-status.js'])

    <x-entity-modal id="proofImageModal" title="Payment Proof" maxWidth="2xl" :hideFooter="true">
        <div class="p-4 flex items-center justify-center bg-gray-50 dark:bg-gray-800">
            <img id="proofImageModalImg" src="" alt="Payment proof"
                class="max-h-[75vh] w-auto max-w-full rounded shadow" />
        </div>
    </x-entity-modal>

    <x-entity-modal id="bftnReceivedModal" title="Confirm BFTN Received" maxWidth="md" formId="bftnReceivedForm"
        submitText="Save Received Date">
        <div class="space-y-4 p-5">
            <p class="text-sm text-gray-600 dark:text-gray-300">Tentative Date: <span id="bftnTentativeDate"
                    class="font-semibold"></span></p>
            <form id="bftnReceivedForm">
                <label for="bftnReceivedAt"
                    class="block text-sm font-semibold text-gray-700 dark:text-gray-200">Received At</label>
                <input id="bftnReceivedAt" type="datetime-local" required
                    class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </form>
        </div>
    </x-entity-modal>
</div>

<div id="verifySalesConfig"
    data-url-template="{{ route('sale.verify', ['id' => '__ID__', 'status' => '__STATUS__']) }}"></div>

@vite(['resources/js/pages/verify-sales.js'])
