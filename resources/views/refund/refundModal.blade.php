<div id="refundModal" class="hidden fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50">
    <div class="bg-white p-6 rounded shadow-lg" style="width: 500px;">
        <h2 class="text-lg font-semibold mb-4">Refund</h2>
        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700">Received Amount</label>
                <input type="text" id="receivedAmountInput" class="border px-3 py-2 mb-4 w-full rounded" readonly placeholder="Received Amount">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Refund Amount</label>
                <input type="number" id="refundAmountInput" class="border px-3 py-2 mb-4 w-full rounded" placeholder="Enter Refunded Amount">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700">Purchase Number of Ticket</label>
                <input type="text" id="PurchaseTicketInput" class="border px-3 py-2 mb-4 w-full rounded" readonly placeholder="Purchase Tickets">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Refund Number Of Ticket</label>
                <input type="number" id="refundTicketInput" class="border px-3 py-2 mb-4 w-full rounded" placeholder="Refund Tickets">
            </div>
        </div>

        <div class="mt-4">
            <label for="remark" class="block text-sm font-medium text-gray-700 mb-2">Remark</label>
            <textarea id="remark" name="remark" rows="3" placeholder="Enter remark (optional)"
                class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 transition"></textarea>
        </div>

        <div class="flex justify-end">
            <button id="submitRefundBtn" class="bg-blue-500 text-white px-4 py-2 rounded">Refund</button>
            <button id="closeModalBtn" class="bg-gray-400 text-white px-4 py-2 ml-2 rounded">Cancel</button>
        </div>
    </div>
</div>

<script>
    // Top-level state shared between the (once-bound) submit handler and refunded().
    let currentRefundSaleId = null;
    let refreshRefundList = null;

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('refundModal');

        function closeModal() {
            modal.classList.add('hidden');
        }

        // Wire close + submit buttons exactly ONCE (no stacked listeners).
        document.getElementById('closeModalBtn').addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.getElementById('submitRefundBtn').addEventListener('click', async () => {
            const refundAmount = document.getElementById('refundAmountInput').value;
            const refundTickets = document.getElementById('refundTicketInput').value;
            const remark = document.getElementById('remark').value;

            if (!refundAmount || !refundTickets) {
                Swal.fire({ title: 'Error!', text: 'Please enter refund amount and number of tickets.', icon: 'error', confirmButtonText: 'OK' });
                return;
            }

            const isConfirmed = await Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to process this refund?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, refund',
                cancelButtonText: 'Cancel',
            });

            if (!isConfirmed.isConfirmed) return;

            try {
                const response = await fetch(`/partial/refund/${currentRefundSaleId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        refunded_amount: refundAmount,
                        refunded_number_of_tickets: refundTickets,
                        remark: remark,
                    }),
                });

                const result = await response.json();
                if (result.success) {
                    Swal.fire({ title: 'Success!', text: 'Refund processed successfully.', icon: 'success', confirmButtonText: 'OK' });
                    closeModal();
                    document.getElementById('refundAmountInput').value = '';
                    document.getElementById('refundTicketInput').value = '';
                    document.getElementById('remark').value = '';
                    if (typeof refreshRefundList === 'function') refreshRefundList();
                } else {
                    Swal.fire({ title: 'Error!', text: result.message || 'Failed to process refund.', icon: 'error', confirmButtonText: 'OK' });
                }
            } catch (error) {
                console.error('Error processing refund:', error);
                Swal.fire({ title: 'Error!', text: 'An error occurred while processing the refund.', icon: 'error', confirmButtonText: 'OK' });
            }
        });
    });

    // Top-level function -> global, directly callable from refund.js (no window. needed).
    function refunded(btn, getList) {
        currentRefundSaleId = btn.dataset.id;
        refreshRefundList = getList;
        document.getElementById('receivedAmountInput').value = btn.dataset.received_total_amount;
        document.getElementById('PurchaseTicketInput').value = btn.dataset.number_ticket;
        document.getElementById('refundModal').classList.remove('hidden');
    }
</script>