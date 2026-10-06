<div id="duePaymentConfig" data-payment-url="{{ route('payments.partial', ['id' => '__ID__']) }}"></div>

<x-entity-modal id="dueModal" title="Paid Due Amount" maxWidth="lg" :hideFooter="true">
    <div class="p-6">
        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700">Total Due Amount</label>
                <input type="text" id="dueAmountInput" class="border px-3 py-2 mb-4 w-full rounded" readonly
                    placeholder="Enter due Amount" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Other Fee (bikas, nogod, vat etc if
                    include) (৳)</label>
                <input type="number" id="otherFeeInput" class="border px-3 py-2 mb-4 w-full rounded"
                    placeholder="0.00" min="0" step="0.01" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Discount Amount (৳)</label>
                <input type="number" id="discountInput" class="border px-3 py-2 mb-4 w-full rounded"
                    placeholder="0.00" min="0" step="0.01" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Total Due</label>
                <input type="text" id="totalDueInput" class="border px-3 py-2 mb-4 w-full rounded" readonly
                    placeholder="Total Due" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Paid Amount</label>
                <input type="number" id="paidAmountInput" class="border px-3 py-2 mb-4 w-full rounded"
                    placeholder="Enter Paid Amount" min="0" step="0.01" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Remaining Due Amount</label>
                <input type="number" id="remainingDueAmountInput" class="border px-3 py-2 mb-4 w-full rounded"
                    placeholder="Remaining Amount" readonly />
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Payment Method <span class="text-red-500">*</span>
            </label>
            <select id="paymentMethodSelect" name="payment_methods"
                class="payment-method-select w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 transition">
                <option value="">Select method</option>
                <option value="Cash">Cash</option>
                <option value="Bkash">Bkash</option>
                <option value="Nagad">Nagad</option>
                <option value="Bank Transfer">Bank Transfer</option>
            </select>
        </div>

        <div id="transactionIdWrapper" class="mt-4 hidden">
            <label for="transactionIdInput" class="block text-sm font-medium text-gray-700 mb-2">
                Transaction ID <span class="text-red-500">*</span>
            </label>
            <input type="text" id="transactionIdInput" placeholder="Enter transaction ID"
                class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 transition" />
        </div>

        <div class="mt-4">
            <label for="paymentProofInput" class="block text-sm font-medium text-gray-700 mb-2">
                Payment Proof (screenshot)
                <span class="text-xs text-gray-500">(transaction ID না থাকলে আপলোড করুন)</span>
            </label>
            <input type="file" id="paymentProofInput" accept="image/*,application/pdf"
                class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 transition" />
            <p id="paymentProofPreview" class="mt-2 text-xs text-gray-500 dark:text-gray-400"></p>
        </div>

        <div class="mt-4">
            <label for="remark" class="block text-sm font-medium text-gray-700 mb-2">
                Remark
            </label>
            <textarea id="remark" name="remark" rows="3" placeholder="Enter remark (optional)"
                class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 transition"></textarea>
        </div>

        <div class="flex justify-end mt-6">
            <button id="submitPaymentBtn" type="button" data-permission="payments.due.collect" class="bg-blue-500 text-white px-4 py-2 rounded">Pay Due</button>
            <button id="closeModalBtn" type="button" class="bg-gray-400 text-white px-4 py-2 ml-2 rounded">Cancel</button>
        </div>
    </div>
</x-entity-modal>



