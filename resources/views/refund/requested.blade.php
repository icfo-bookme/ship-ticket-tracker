<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Requested Refunds</h2>

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
                    { data: 'refunded_number_of_tickets' },
                    { data: 'gross_refund_amount' },
                    { data: 'customer_charge_percent' },
                    { data: 'partner_share_percent' },
                    { data: 'customer_refund_amount' },
                    { data: 'partner_share_amount' },
                    { data: 'company_retained_amount' },
                    { data: 'status' },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: (data, type, row) => type !== 'display' ? '' :
                            `<button class="fas fa-edit text-blue-950 px-2 py-1 rounded requestedEditBtn"
                                data-id="${row.sale?.id ?? ''}"
                                data-request-id="${row.id}"
                                data-received_total_amount="${row.sale?.ticket_fee ?? row.gross_refund_amount}"
                                data-categories="${encodeURIComponent(JSON.stringify(row.tickets || []))}"
                                data-gross-amount="${row.gross_refund_amount ?? 0}"
                                data-ticket-count="${row.refunded_number_of_tickets ?? 0}"
                                data-customer-charge="${row.customer_charge_percent ?? 0}"
                                data-partner-share="${row.partner_share_percent ?? 0}"
                                title="Edit request"></button>
                            <button class="bg-yellow-600 text-white px-2 py-1 rounded cancelRefundBtn"
                                data-id="${row.id}" title="Cancel refund request">Cancel</button>
                            <button class="bg-green-700 text-white px-2 py-1 rounded customerRefundBtn"
                                data-id="${row.id}" title="Refund customer">Refunded</button>`,
                    },
                ];

                window.dataTableFilters = window.dataTableFilters || {};
                window.dataTableFilters['requestedRefundsTable'] = () => ({
                    ship_id: document.getElementById('requestedShip').value,
                    company_id: document.getElementById('requestedCompany').value,
                    journey_date: document.getElementById('requestedJourneyDate').value,
                });

                function formatRequestedDate(value) {
                    return value ? new Date(value).toLocaleDateString() : 'N/A';
                }

                async function refundCustomer(button, getList) {
                    const confirmation = await Swal.fire({
                        title: 'Are you sure?',
                        text: 'Refund this amount to the customer now?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, refund customer',
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
                    const result = await response.json();
                    await Swal.fire({
                        title: result.success ? 'Refunded' : 'Error',
                        text: result.message || 'Customer refund failed.',
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

                        const customerRefundButton = event.target.closest('.customerRefundBtn');
                        if (customerRefundButton) refundCustomer(customerRefundButton, window.getList);

                        const cancelRefundButton = event.target.closest('.cancelRefundBtn');
                        if (cancelRefundButton) cancelRefund(cancelRefundButton, window.getList);
                    });
                });
            </script>

            <x-data-table id="requestedRefundsTable" :headings="[
                'Request ID', 'Sale ID', 'Customer', 'Journey Date', 'Type',
                'Tickets', 'Gross Amount', 'Customer Charge %', 'Partner Share %',
                'Customer Refund', 'Partner Share', 'Company Retained', 'Status', 'Action'
            ]" url="/all/refund-requests" :ordering="false" :delegateActions="false" :order="[]" />
        </div>
    </div>
    @include('refund.refundModal')
</x-app-layout>
