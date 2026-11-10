
<x-entity-modal id="refundModal" title="Partial Refund Request" maxWidth="2xl" :hideFooter="true">
    <div class="p-6">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700">Sale Ticket Price</label>
                <input type="text" id="receivedAmountInput" class="border px-3 py-2 mb-4 w-full rounded" readonly
                    placeholder="Ticket price">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Selected Ticket Refund Amount (Net)</label>
                <input type="number" id="refundAmountInput" class="border px-3 py-2 mb-4 w-full rounded bg-gray-50"
                    placeholder="Calculated after discount" >
                <p id="refundBreakdownNote" class="-mt-3 text-xs text-gray-500" aria-live="polite">
                    Ticket subtotal 0.00 - discount 0.00 = 0.00
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

        <section class="mt-4 flex flex-col gap-4 rounded-lg border border-gray-200 p-4 dark:border-gray-600">
            <label for="companyErrorInput" class="flex items-center gap-2 text-sm font-medium text-gray-800 dark:text-gray-100">
                <input id="companyErrorInput" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Company Error / Company Loss
            </label>
            <div id="companyErrorFields" class="hidden flex-col gap-4">
                <div>
                    <label for="companyLossTypeInput" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Loss Type</label>
                    <select id="companyLossTypeInput" disabled class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="partner_share">Partner Share Loss</option>
                        <option value="custom">Custom Loss</option>
                    </select>
                </div>
                <div id="companyLossPercentField" class="hidden">
                    <label for="companyLossPercentInput" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Company Loss (%)</label>
                    <input id="companyLossPercentInput" type="number" min="0" max="100" step="0.01" disabled class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-200">Company loss: <strong id="companyLossAmountPreview" aria-live="polite">0.00</strong></p>
                <p class="text-xs text-gray-500 dark:text-gray-400">This loss is included in the refund value and does not reduce the customer refund. Custom loss is based on the selected ticket subtotal before discount.</p>
                <div>
                    <label for="companyErrorReasonInput" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Company Error Reason</label>
                    <textarea id="companyErrorReasonInput" rows="3" maxlength="2000" disabled class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
            </div>
        </section>

        <div class="mt-4 grid grid-cols-1 gap-5 rounded bg-amber-50 p-3 text-sm sm:grid-cols-3">
            <span>Current Due: <strong id="refundDueAmountPreview">0.00</strong></span>
            <span>Due Adjusted: <strong id="dueAdjustmentPreview">0.00</strong></span>
            <span>Customer Refund After Adjustment: <strong id="payableRefundPreview">0.00</strong></span>
        </div>

        <div class="mt-3 rounded border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" role="note">
            <span class="font-semibold">NB:</span>
            The customer paid an extra <strong id="refundExtraReceivedPreview">0.00</strong>.
            To refund it, submit a request from the Extra Received section.
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
            <button id="submitRefundBtn" type="button" data-permission="refunds.create" class="bg-blue-500 text-white px-4 py-2 rounded">Submit Refund Request</button>
            <button id="closeModalBtn" type="button" class="bg-gray-400 text-white px-4 py-2 ml-2 rounded">Cancel</button>
        </div>
    </div>
</x-entity-modal>



