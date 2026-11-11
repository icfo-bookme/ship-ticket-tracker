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
                    'ID', 'Customer Name', 'Ship Name', 'Journey Date',
                    'Purchase Num Of Tickets', 'Refunded Num Of Tickets', 'Gross Amount',
                    'Customer Refund', 'Partner Share', 'Company Retained', 'Due Adjusted',
                    'Final Customer Refund', 'Settlement / New Sale', 'Status', 'Action',
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
    <x-entity-modal id="refundPaymentDetailsModal" title="Refund Payment Details" formId="refundPaymentDetailsForm" submitText="Save Details">
        <form id="refundPaymentDetailsForm" class="flex flex-col gap-3 p-4">
            <label for="refundPaymentDetailsInput" class="text-sm font-medium text-gray-700 dark:text-gray-200">Payment Details</label>
            <textarea id="refundPaymentDetailsInput" name="refund_payment_details" required maxlength="5000" rows="5"
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                placeholder="Write payment method, reference, date, or other details..."></textarea>
            <p id="refundPaymentDetailsError" role="alert" class="text-sm text-red-600 dark:text-red-400" hidden></p>
        </form>
    </x-entity-modal>
</x-app-layout>


