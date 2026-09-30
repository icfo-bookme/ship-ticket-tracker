<x-app-layout>
    <div class="py-6">
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

            <script>
                window.dataTableColumns = window.dataTableColumns || {};
                window.dataTableColumns['requestedRefundsTable'] = [
                    { data: 'id' },
                    { data: 'sale.id', render: (data) => data || 'N/A' },
                    { data: 'sale.customer_name', render: (data) => escapeHtml(data || 'N/A') },
                    { data: 'sale.journey_date', render: formatRequestedDate },
                    { data: 'refund_type' },
                    { data: 'total_purchase_tickets' },
                    { data: 'total_refund_tickets' },
                    { data: 'gross_refund_amount' },
                    { data: 'customer_refund_amount' },
                    { data: 'partner_share_amount' },
                    { data: 'company_retained_amount' },
                    { data: 'due_adjusted_amount', render: (data) => Number(data || 0).toFixed(2) },
                    {
                        data: 'customer_refund_after_due_adjustment',
                        render: (data) => `<div class="bg-red-100 px-2 py-1 font-semibold text-red-700">${Number(data || 0).toFixed(2)}</div>`,
                    },
                    @if ($refundStatus === 'payment_details_added')
                    { data: 'refund_payment_details', render: (data) => escapeHtml(data || 'N/A') },
                    @endif
                    { data: 'status' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: (data, type, row) => type !== 'display' ? '' :
                            `<div class="flex items-center gap-2 pb-2">
                            <button class="fas fa-edit text-blue-950 px-2 py-1 rounded requestedEditBtn"
                                data-id="${row.sale?.id ?? ''}"
                                data-request-id="${row.id}"
                                data-received_total_amount="${row.sale?.ticket_fee ?? row.gross_refund_amount}"
                                data-categories="${encodeURIComponent(JSON.stringify(row.edit_categories || []))}"
                                data-gross-amount="${row.gross_refund_amount ?? 0}"
                                data-ticket-count="${row.refunded_number_of_tickets ?? 0}"
                                data-customer-charge="${row.customer_charge_percent ?? 0}"
                                data-partner-share="${row.partner_share_percent ?? 0}"
                                title="Edit request"></button>
                            <button class="bg-yellow-600 text-white px-2 py-1 rounded cancelRefundBtn"
                                data-id="${row.id}" title="Cancel refund request">Cancel</button>
                            @if ($refundStatus === 'requested')
                            <button class="bg-green-700 text-white px-2 py-1 rounded approveRefundBtn"
                                data-id="${row.id}" title="Approve refund request">Approve</button>
                            @endif
                            @if ($refundStatus === 'partner_approved')
                            <button class="bg-blue-700 text-white px-2 py-1 rounded addPaymentDetailsBtn"
                                data-id="${row.id}" title="Add refund payment details">Add Payment Details</button>
                            @endif
                            @if ($refundStatus === 'payment_details_added')
                            <button class="bg-green-700 text-white px-2 py-1 rounded refundCustomerBtn"
                                data-id="${row.id}" title="Refund customer">Refund</button>
                            @endif
                            </div>`,
                    },
                ];

                window.dataTableFilters = window.dataTableFilters || {};
                window.dataTableFilters['requestedRefundsTable'] = () => ({
                    ship_id: document.getElementById('requestedShip').value,
                    company_id: document.getElementById('requestedCompany').value,
                    journey_date: document.getElementById('requestedJourneyDate').value,
                    search: new URLSearchParams(window.location.search).get('search')
                        ? { value: new URLSearchParams(window.location.search).get('search') }
                        : undefined,
                });

                function formatRequestedDate(value) {
                    return value ? new Date(value).toLocaleDateString() : 'N/A';
                }

                async function approveRefund(button, getList) {
                    const confirmation = await Swal.fire({
                        title: 'Approve refund request?',
                        text: 'This request will move to the partner-approved stage.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, approve',
                        cancelButtonText: 'Cancel',
                    });

                    if (!confirmation.isConfirmed) return;

                    const response = await fetch(`/refunds/${button.dataset.id}/approve`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    });
                    const result = await response.json();
                    await Swal.fire({
                        title: result.success ? 'Approved' : 'Error',
                        text: result.message || 'Refund approval failed.',
                        icon: result.success ? 'success' : 'error',
                    });
                    if (result.success) getList();
                }

                async function cancelRefund(button, getList) {
                    const confirmation = await Swal.fire({
                        title: 'Cancel refund request?',
                        text: 'This request will be removed from the requested list.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, cancel request',
                        cancelButtonText: 'Keep request',
                    });

                    if (!confirmation.isConfirmed) return;

                    const response = await fetch(`/refunds/${button.dataset.id}/cancel`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    });
                    const result = await response.json();
                    await Swal.fire({
                        title: result.success ? 'Cancelled' : 'Error',
                        text: result.message || 'Could not cancel refund request.',
                        icon: result.success ? 'success' : 'error',
                    });
                    if (result.success) getList();
                }

                async function addPaymentDetails(button, getList) {
                    const result = await Swal.fire({
                        title: 'Add Refund Payment Details',
                        input: 'textarea',
                        inputLabel: 'Refund Payment Details',
                        inputPlaceholder: 'Write payment method, reference, date, or other details...',
                        showCancelButton: true,
                        confirmButtonText: 'Save Details',
                        cancelButtonText: 'Cancel',
                        inputValidator: (value) => !value?.trim() ? 'Payment details are required.' : undefined,
                    });

                    if (!result.isConfirmed) return;

                    const response = await fetch(`/refunds/${button.dataset.id}/payment-details`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({ refund_payment_details: result.value.trim() }),
                    });
                    const responseData = await response.json();

                    await Swal.fire({
                        title: responseData.success ? 'Saved' : 'Error',
                        text: responseData.message || responseData.errors?.refund_payment_details?.[0] || 'Could not save payment details.',
                        icon: responseData.success ? 'success' : 'error',
                    });

                    if (responseData.success) getList();
                }

                async function refundCustomer(button, getList) {
                    const confirmation = await Swal.fire({
                        title: 'Complete customer refund?',
                        text: 'This refund will be marked as completed.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, refund',
                        cancelButtonText: 'Cancel',
                    });

                    if (!confirmation.isConfirmed) return;

                    const response = await fetch(`/refunds/${button.dataset.id}/customer-payment`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    });
                    const responseData = await response.json();

                    await Swal.fire({
                        title: responseData.success ? 'Refunded' : 'Error',
                        text: responseData.message || 'Could not complete the refund.',
                        icon: responseData.success ? 'success' : 'error',
                    });

                    if (responseData.success) getList();
                }

                document.addEventListener('DOMContentLoaded', () => {
                    ['requestedShip', 'requestedCompany', 'requestedJourneyDate'].forEach((id) => {
                        document.getElementById(id)?.addEventListener('change', () => window.getList());
                    });
                    document.getElementById('requestedRefundsTable')?.addEventListener('click', (event) => {
                        const button = event.target.closest('.requestedEditBtn');
                        if (button) {
                            refunded(button, window.getList, true);
                            return;
                        }

                        const approveRefundButton = event.target.closest('.approveRefundBtn');
                        if (approveRefundButton) approveRefund(approveRefundButton, window.getList);

                        const cancelRefundButton = event.target.closest('.cancelRefundBtn');
                        if (cancelRefundButton) cancelRefund(cancelRefundButton, window.getList);

                        const paymentDetailsButton = event.target.closest('.addPaymentDetailsBtn');
                        if (paymentDetailsButton) addPaymentDetails(paymentDetailsButton, window.getList);

                        const refundCustomerButton = event.target.closest('.refundCustomerBtn');
                        if (refundCustomerButton) refundCustomer(refundCustomerButton, window.getList);
                    });
                });
            </script>

            @php
                $tableHeadings = [
                    'Request ID', 'Sale ID', 'Customer', 'Journey Date', 'Type',
                    'Total Purchase Tickets', 'Total Refund Tickets', 'Gross Amount',
                    'Customer Refund', 'Partner Share', 'Company Retained', 'Due Adjusted', 'Final Customer Refund',
                ];

                if ($refundStatus === 'payment_details_added') {
                    $tableHeadings[] = 'Payment Details';
                }

                $tableHeadings[] = 'Status';
                $tableHeadings[] = 'Action';
            @endphp
            <x-data-table id="requestedRefundsTable" :headings="$tableHeadings" url="{{ url('/all/refund-requests') }}?status={{ $refundStatus }}" :ordering="false" :delegateActions="false" :order="[]" />
        </div>
    </div>
    @include('refund.refundModal')
</x-app-layout>
