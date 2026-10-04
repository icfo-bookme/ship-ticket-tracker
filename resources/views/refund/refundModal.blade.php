
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
            <span>Partner share: <strong id="partnerSharePreview">0.00</strong></span>
            <span>Company retains: <strong id="companyRetainedPreview">0.00</strong></span>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-5 rounded bg-amber-50 p-3 text-sm sm:grid-cols-3">
            <span>Current Due: <strong id="refundDueAmountPreview">0.00</strong></span>
            <span>Due Adjusted: <strong id="dueAdjustmentPreview">0.00</strong></span>
            <span>Customer Refund After Adjustment: <strong id="payableRefundPreview">0.00</strong></span>
        </div>

        <div class="mt-4 rounded bg-red-100 px-4 py-3 text-center text-red-800">
            <span class="font-semibold">Final Customer Refund:</span>
            <strong id="finalCustomerRefundPreview" class="ml-2 text-lg">0.00</strong>
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




