@props(['saleId' => null])
                    <div id="refundAdjustment" data-url="{{ route('ship-ticket-sales.refund-adjustments') }}"
                        data-sale-id="{{ $saleId }}" data-refund-id="{{ old('refund_id') }}" data-amount="{{ old('refund_adjustment_amount') }}"
                        class="rounded-lg border border-blue-200 p-4 dark:border-blue-800">
                        <label class="flex items-center gap-2 text-sm font-medium dark:text-gray-100">
                            <input type="checkbox" id="adjustFromRefund" class="rounded border-gray-300 text-blue-600">
                            Adjust from refund
                        </label>
                        <div id="refundAdjustmentFields" class="mt-3 hidden space-y-3">
                            <label for="refund_id" class="block text-sm dark:text-gray-200">Refund</label>
                            <select id="refund_id" name="refund_id" disabled class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white">
                                <option value="">Select a refund</option>
                            </select>
                            <p id="refundAdjustmentBalance" role="status" class="text-sm text-gray-600 dark:text-gray-300">Requested refunds become available after partner approval.</p>
                            <label for="refund_adjustment_amount" class="block text-sm dark:text-gray-200">Refund Adjusted amount</label>
                            <input type="number" id="refund_adjustment_amount" name="refund_adjustment_amount" min="0.01" step="0.01" disabled
                                class="w-full rounded-lg border-gray-300 dark:bg-gray-700 dark:text-white">
                            <p id="refundAdjustmentPayment" class="font-semibold text-blue-700 dark:text-blue-300"></p>
                            @error('refund_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                            @error('refund_adjustment_amount') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
