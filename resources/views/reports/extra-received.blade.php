<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Extra Received Sales</h2>
            </div>

            <script>
                window.dataTableColumns = window.dataTableColumns || {};
                window.dataTableColumns['extraReceivedTable'] = [
                    { data: 'id' },
                    { data: 'customer_name', render: (data) => escapeHtml(data || 'N/A') },
                    { data: 'customer_mobile', render: (data) => escapeHtml(data || 'N/A') },
                    { data: 'ship_name', render: (data) => escapeHtml(data || 'N/A') },
                    { data: 'journey_date', render: (data) => data ? new Date(data).toLocaleDateString() : 'N/A' },
                    { data: 'total_payable', render: formatCurrency },
                    { data: 'received_amount', render: formatCurrency },
                    { data: 'extra_received_amount', render: formatCurrency },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: (data, type, row) => type !== 'display' ? '' : `
                            <div class="flex gap-2 justify-center">
                                <button type="button" class="bg-red-600 text-white px-2 py-1 rounded refundExtraBtn" data-id="${row.id}">
                                    Refund Extra
                                </button>
                                <button type="button" class="bg-blue-600 text-white px-2 py-1 rounded adjustExtraBtn" data-id="${row.id}">
                                    Adjust to Other Fee
                                </button>
                            </div>`,
                    },
                ];

                function formatCurrency(value) {
                    return Number(value || 0).toFixed(2);
                }

                document.addEventListener('DOMContentLoaded', () => {
                    document.getElementById('extraReceivedTable')?.addEventListener('click', async (event) => {
                        const button = event.target.closest('.adjustExtraBtn');
                        const refundButton = event.target.closest('.refundExtraBtn');

                        if (refundButton) {
                            const confirmation = await Swal.fire({
                                title: 'Create refund request?',
                                text: 'A refund request will be created for the extra received amount.',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonText: 'Yes, create request',
                                cancelButtonText: 'Cancel',
                            });

                            if (!confirmation.isConfirmed) {
                                return;
                            }

                            const response = await fetch(`/extra-received/${refundButton.dataset.id}/refund`, {
                                method: 'POST',
                                headers: {
                                    Accept: 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                },
                            });
                            const result = await response.json();

                            await Swal.fire({
                                title: result.success ? 'Request created' : 'Error',
                                text: result.message || 'Could not create the refund request.',
                                icon: result.success ? 'success' : 'error',
                            });

                            if (result.success) {
                                window.getList();
                            }

                            return;
                        }

                        if (!button) {
                            return;
                        }

                        const confirmation = await Swal.fire({
                            title: 'Adjust extra amount?',
                            text: 'The extra received amount will be added to Other Fee.',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, adjust it',
                            cancelButtonText: 'Cancel',
                        });

                        if (!confirmation.isConfirmed) {
                            return;
                        }

                        const response = await fetch(`/extra-received/${button.dataset.id}/adjust-to-other-fee`, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                        });
                        const result = await response.json();

                        await Swal.fire({
                            title: result.success ? 'Adjusted' : 'Error',
                            text: result.message || 'Could not adjust the extra amount.',
                            icon: result.success ? 'success' : 'error',
                        });

                        if (result.success) {
                            window.getList();
                        }
                    });
                });
            </script>

            <x-data-table id="extraReceivedTable" :headings="[
                'Sale ID', 'Customer Name', 'Mobile', 'Ship', 'Journey Date',
                'Total Payable', 'Received Amount', 'Extra Received Amount', 'Action'
            ]" url="/extra-received/data" :ordering="false" :delegateActions="false" :order="[]" />
        </div>
    </div>
</x-app-layout>
