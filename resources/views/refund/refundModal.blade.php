
<x-entity-modal id="refundModal" title="Partial Refund Request" maxWidth="2xl" :hideFooter="true">
    <div class="p-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700">Sale Ticket Fee</label>
                <input type="text" id="receivedAmountInput" class="border px-3 py-2 mb-4 w-full rounded" readonly
                    placeholder="Ticket fee">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Selected Ticket Refund Amount (Net)</label>
                <input type="number" id="refundAmountInput" class="border px-3 py-2 mb-4 w-full rounded bg-gray-50"
                    placeholder="Calculated after fee and discount" readonly>
                <p id="refundBreakdownNote" class="-mt-3 text-xs text-gray-500" aria-live="polite">
                    Ticket subtotal 0.00 - other fee 0.00 - discount 0.00 = 0.00
                </p>
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
            <span>Customer charge: <strong id="customerChargePreview">0.00</strong></span>
            <span>Partner share: <strong id="partnerSharePreview">0.00</strong></span>
            <span>Company retains: <strong id="companyRetainedPreview">0.00</strong></span>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-5 rounded bg-amber-50 p-3 text-sm sm:grid-cols-3">
            <span>Current Due: <strong id="refundDueAmountPreview">0.00</strong></span>
            <span>Due Adjusted: <strong id="dueAdjustmentPreview">0.00</strong></span>
            <span>Customer Refund After Adjustment: <strong id="payableRefundPreview">0.00</strong></span>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-5 rounded bg-emerald-50 p-3 text-sm sm:grid-cols-2">
            <span>Extra Received: <strong id="refundExtraReceivedPreview">0.00</strong></span>
            <span>Extra Available to Refund: <strong id="refundExtraAvailablePreview">0.00</strong></span>
        </div>

        <div class="mt-4 rounded bg-red-100 px-4 py-3 text-center text-red-800">
            <span class="font-semibold">Final Customer Refund:</span>
            <strong id="finalCustomerRefundPreview" class="ml-2 text-lg">0.00</strong>
        </div>

        <div class="mt-5">
            <h3 class="mb-2 font-semibold text-gray-800">Tickets to return</h3>
            <div id="refundCategoryRows" class="space-y-2"></div>
            <p class="mt-2 text-sm text-gray-500">Enter a quantity from 0 up to the purchased quantity for each category.</p>
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




