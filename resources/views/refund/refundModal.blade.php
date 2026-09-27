<x-entity-modal id="refundModal" title="Partial Refund Request" maxWidth="2xl" :hideFooter="true">
    <div class="p-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700">Total Ticket Price</label>
                <input type="text" id="receivedAmountInput" class="border px-3 py-2 mb-4 w-full rounded" readonly
                    placeholder="Received Amount">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Selected Ticket Refund Amount</label>
                <input type="number" id="refundAmountInput" class="border px-3 py-2 mb-4 w-full rounded"
                    placeholder="Enter Refunded Amount">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Selected Tickets</label>
                <input type="text" id="selectedTicketCountInput" class="border px-3 py-2 mb-4 w-full rounded" value="0" readonly>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700">Customer Charge (%)</label>
                <input type="number" id="customerChargePercentInput" min="0" max="100" step="0.01" value="0" class="border px-3 py-2 mb-4 w-full rounded">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Partner Share (%)</label>
                <input type="number" id="partnerSharePercentInput" min="0" max="100" step="0.01" value="0" class="border px-3 py-2 mb-4 w-full rounded">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 rounded bg-gray-50 p-3 text-sm sm:grid-cols-3">
            <span>Customer receives: <strong id="customerRefundPreview">0.00</strong></span>
            <span>Partner share: <strong id="partnerSharePreview">0.00</strong></span>
            <span>Company retains: <strong id="companyRetainedPreview">0.00</strong></span>
        </div>

        <div class="mt-5">
            <h3 class="mb-2 font-semibold text-gray-800">Tickets to return</h3>
            <div id="refundCategoryRows" class="space-y-2"></div>
            <p class="mt-2 text-sm text-gray-500">Set return quantity to 0 for categories that should not be refunded.</p>
        </div>

        <div class="mt-4">
            <label for="remark" class="block text-sm font-medium text-gray-700 mb-2">Remark</label>
            <textarea id="remark" name="remark" rows="3" placeholder="Enter remark (optional)"
                class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 transition"></textarea>
        </div>

        <div class="flex justify-end mt-6">
            <button id="submitRefundBtn" type="button" class="bg-blue-500 text-white px-4 py-2 rounded">Submit Refund Request</button>
            <button id="closeModalBtn" type="button" class="bg-gray-400 text-white px-4 py-2 ml-2 rounded">Cancel</button>
        </div>
    </div>
</x-entity-modal>

<script>
    let currentRefundSaleId = null;
    let currentRefundRequestId = null;
    let currentRefundEditing = false;
    let refreshRefundList = null;

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('refundModal');
        const grossAmountInput = document.getElementById('refundAmountInput');
        const customerChargeInput = document.getElementById('customerChargePercentInput');
        const partnerShareInput = document.getElementById('partnerSharePercentInput');

        function updateRefundPreview() {
            const amount = Number(grossAmountInput.value || 0);
            const customerCharge = amount * Number(customerChargeInput.value || 0) / 100;
            const partnerShare = amount * Number(partnerShareInput.value || 0) / 100;
            document.getElementById('customerRefundPreview').textContent = (amount - customerCharge).toFixed(2);
            document.getElementById('partnerSharePreview').textContent = partnerShare.toFixed(2);
            document.getElementById('companyRetainedPreview').textContent = (customerCharge - partnerShare).toFixed(2);
        }

        [grossAmountInput, customerChargeInput, partnerShareInput].forEach((input) => input.addEventListener('input', updateRefundPreview));

        function closeModal() {
            modal._closeModal();
        }

        document.getElementById('closeModalBtn').addEventListener('click', closeModal);

        document.getElementById('submitRefundBtn').addEventListener('click', async () => {
            const refundAmount = document.getElementById('refundAmountInput').value;
            const refundTickets = Array.from(document.querySelectorAll('.refundCategoryQuantity'))
                .reduce((total, input) => total + Number(input.value || 0), 0);
            const ticketSelections = Array.from(document.querySelectorAll('.refundCategoryQuantity')).map((input) => ({
                category_id: input.dataset.categoryId,
                refunded_quantity: Number(input.value || 0),
            })).filter((selection) => selection.refunded_quantity > 0);
            const remark = document.getElementById('remark').value;
            const customerChargePercent = customerChargeInput.value;
            const partnerSharePercent = partnerShareInput.value;

            if (! refundAmount || ! refundTickets || ! ticketSelections.length) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please enter refund amount and number of tickets.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            const isConfirmed = await Swal.fire({
                title: 'Are you sure?',
                    text: 'Send this refund request to the partner?',
                icon: 'warning',
                showCancelButton: true,
                    confirmButtonText: 'Yes, send request',
                cancelButtonText: 'Cancel',
            });

            if (! isConfirmed.isConfirmed) return;

            try {
                const response = await fetch(currentRefundEditing
                    ? `/refunds/${currentRefundRequestId}`
                    : `/partial/refund/${currentRefundSaleId}`, {
                    method: currentRefundEditing ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        refunded_amount: refundAmount,
                        refunded_number_of_tickets: refundTickets,
                        ticket_selections: ticketSelections,
                        customer_charge_percent: customerChargePercent,
                        partner_share_percent: partnerSharePercent,
                        remark: remark,
                    }),
                });

                const responseText = await response.text();
                let result;

                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    throw new Error(`Refund request failed with HTTP ${response.status}.`);
                }

                if (result.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: currentRefundEditing ? 'Refund request updated.' : 'Refund request sent to partner.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    closeModal();
                    document.getElementById('refundAmountInput').value = '';
                    document.getElementById('remark').value = '';
                    if (typeof refreshRefundList === 'function') refreshRefundList();
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: result.message || result.errors?.ticket_selections?.[0] || 'Failed to process refund.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (error) {
                console.error('Error processing refund:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while processing the refund.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    function refunded(btn, getList, editing = false) {
        currentRefundSaleId = btn.dataset.id;
        currentRefundRequestId = editing ? btn.dataset.requestId : null;
        currentRefundEditing = editing;
        document.getElementById('submitRefundBtn').textContent = editing
            ? 'Update Refund Request'
            : 'Submit Refund Request';
        refreshRefundList = getList;
        document.getElementById('receivedAmountInput').value = btn.dataset.received_total_amount;
        document.getElementById('customerChargePercentInput').value = editing ? (btn.dataset.customerCharge || 0) : 0;
        document.getElementById('partnerSharePercentInput').value = editing ? (btn.dataset.partnerShare || 0) : 0;
        document.getElementById('refundAmountInput').value = editing ? (btn.dataset.grossAmount || 0) : 0;
        document.getElementById('selectedTicketCountInput').value = 0;
        const grossAmount = Number(editing ? (btn.dataset.grossAmount || 0) : 0);
        const customerCharge = grossAmount * Number(editing ? (btn.dataset.customerCharge || 0) : 0) / 100;
        const partnerShare = grossAmount * Number(editing ? (btn.dataset.partnerShare || 0) : 0) / 100;
        document.getElementById('customerRefundPreview').textContent = (grossAmount - customerCharge).toFixed(2);
        document.getElementById('partnerSharePreview').textContent = partnerShare.toFixed(2);
        document.getElementById('companyRetainedPreview').textContent = (customerCharge - partnerShare).toFixed(2);
        const categories = JSON.parse(decodeURIComponent(btn.dataset.categories || '[]'));
        document.getElementById('refundCategoryRows').innerHTML = categories.map((category) => {
            const price = Number(category.package?.price || category.unit_amount || 0);
            const roundTripTotal = Number(category.package?.round_trip_price || 0);
            const returnPrice = roundTripTotal > 0 ? roundTripTotal - price : price;
            const purchasedQuantity = Number(category.quantity || category.purchased_quantity || 0);
            const refundedQuantity = editing
                ? Number(category.refunded_quantity ?? category.quantity_refunded ?? 0)
                : 0;

            return `<div class="grid grid-cols-5 items-center gap-2 rounded border p-2 text-sm">
                <span class="col-span-2">${escapeHtml(category.package?.name || 'Ticket')}</span>
                <span>${escapeHtml(category.type || '')}</span>
                <span>Qty: ${purchasedQuantity}<br>৳${(category.type === 'return' ? returnPrice : price).toFixed(2)}</span>
                <input type="number" min="0" max="${purchasedQuantity}" value="${refundedQuantity}"
                    data-category-id="${category.category_id ?? category.id}" data-price="${category.type === 'return' ? returnPrice : price}"
                    class="refundCategoryQuantity w-full rounded border px-2 py-1" aria-label="Return quantity">
            </div>`;
        }).join('');
        document.querySelectorAll('.refundCategoryQuantity').forEach((input) => input.addEventListener('input', updateRefundAmount));
        updateRefundAmount();
        if (editing && !Number(document.getElementById('selectedTicketCountInput').value || 0)) {
            document.getElementById('selectedTicketCountInput').value = btn.dataset.ticketCount || 0;
        }
        document.getElementById('refundModal').classList.remove('hidden');
        document.getElementById('refundModal').classList.add('flex');
    }

    function updateRefundAmount() {
        const inputs = Array.from(document.querySelectorAll('.refundCategoryQuantity'));
        const amount = inputs.reduce((total, input) => total + Number(input.value || 0) * Number(input.dataset.price || 0), 0);
        const ticketCount = inputs.reduce((total, input) => total + Number(input.value || 0), 0);
        document.getElementById('refundAmountInput').value = amount.toFixed(2);
        document.getElementById('selectedTicketCountInput').value = ticketCount;
        document.getElementById('refundAmountInput').dispatchEvent(new Event('input'));
    }
</script>
