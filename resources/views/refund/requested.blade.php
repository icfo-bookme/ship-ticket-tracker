<x-app-layout>
    @vite(['resources/js/pages/refunds.js', 'resources/js/pages/refund-requests.js'])
    <div id="refundRequestPage" data-status="{{ $refundStatus }}" data-approve-url="{{ route('refunds.approve', ['id' => '__ID__']) }}" data-cancel-url="{{ route('refunds.cancel', ['id' => '__ID__']) }}" data-payment-details-url="{{ route('refunds.payment-details', ['id' => '__ID__']) }}" data-customer-payment-url="{{ route('refunds.customer-payment', ['id' => '__ID__']) }}" class="py-6">
        <div class=" mx-auto sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $pageTitle }}</h2>

            <div class="mt-6 mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label for="requestedShip" class="block text-sm font-medium text-gray-700">Ship</label>
                    <select id="requestedShip" class="mt-1 block w-full border-gray-300 rounded-md">
                        <option value="">All Ships</option>
                        @foreach ($ships as $ship)
                            <option value="{{ $ship->id }}">{{ $ship->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="requestedCompany" class="block text-sm font-medium text-gray-700">Company</label>
                    <select id="requestedCompany" class="mt-1 block w-full border-gray-300 rounded-md">
                        <option value="">All Companies</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                <label for="requestedJourneyDate" class="block text-sm font-medium text-gray-700">Journey Date</label>
                <input type="date" id="requestedJourneyDate" class="mt-1 block w-full border-gray-300 rounded-md">
                </div>
            </div>

            

            @php
                $tableHeadings = $refundStatus === 'completed' ? [
                    'ID', 'Customer Name', 'Mobile', 'Ship Name', 'Journey Date',
                    'Purchase Num Of Tickets', 'Refunded Num Of Tickets', 'Gross Amount',
                    'Customer Refund', 'Partner Share', 'Company Retained', 'Due Adjusted',
                    'Final Customer Refund', 'Status', 'Action',
                ] : [
                    'Request ID', 'Sale ID', 'Customer', 'Journey Date', 'Type',
                    'Total Purchase Tickets', 'Total Refund Tickets', 'Gross Amount',
                    'Customer Refund', 'Partner Share', 'Company Retained', 'Due Adjusted', 'Final Customer Refund',
                ];

                if ($refundStatus !== 'completed' && $refundStatus === 'payment_details_added') {
                    $tableHeadings[] = 'Payment Details';
                }

                if ($refundStatus !== 'completed') {
                    $tableHeadings[] = 'Status';
                    $tableHeadings[] = 'Action';
                }
            @endphp
            <x-data-table id="requestedRefundsTable" :headings="$tableHeadings" url="{{ route('refunds.requests.data') }}?status={{ $refundStatus }}" :ordering="false" :delegateActions="false" :order="[]" />
        </div>
    </div>
    @include('refund.refundModal')
</x-app-layout>


