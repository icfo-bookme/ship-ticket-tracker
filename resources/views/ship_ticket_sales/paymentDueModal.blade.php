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
            <button id="submitPaymentBtn" type="button" class="bg-blue-500 text-white px-4 py-2 rounded">Pay Due</button>
            <button id="closeModalBtn" type="button" class="bg-gray-400 text-white px-4 py-2 ml-2 rounded">Cancel</button>
        </div>
    </div>
</x-entity-modal>

<script>
    function due(btn, getList) {
        const saleId = btn.dataset.id;
        const dueTotalAmount = parseFloat(btn.dataset.due_amount) || 0;

        const dueAmountInput = document.getElementById('dueAmountInput');
        const otherFeeInput = document.getElementById('otherFeeInput');
        const discountInput = document.getElementById('discountInput');
        const totalDueInput = document.getElementById('totalDueInput');
        const paidAmountInput = document.getElementById('paidAmountInput');
        const remainingDueAmountInput = document.getElementById('remainingDueAmountInput');
        const paymentMethodSelect = document.getElementById('paymentMethodSelect');
        const transactionIdWrapper = document.getElementById('transactionIdWrapper');
        const transactionIdInput = document.getElementById('transactionIdInput');
        const paymentProofInput = document.getElementById('paymentProofInput');
        const paymentProofPreview = document.getElementById('paymentProofPreview');

        dueAmountInput.value = dueTotalAmount.toFixed(2);
        otherFeeInput.value = '0.00';
        discountInput.value = '0.00';
        totalDueInput.value = dueTotalAmount.toFixed(2);
        paidAmountInput.value = '';
        remainingDueAmountInput.value = dueTotalAmount.toFixed(2);
        paymentMethodSelect.value = '';
        transactionIdInput.value = '';
        transactionIdWrapper.classList.add('hidden');
        resetPaymentProof();
        document.getElementById('remark').value = '';

        const modal = document.getElementById('dueModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        const closeBtn = document.getElementById('closeModalBtn');
        const submitBtn = document.getElementById('submitPaymentBtn');

        const getSummary = () => {
            const otherFee = parseFloat(otherFeeInput.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            const paidAmount = parseFloat(paidAmountInput.value) || 0;
            const totalDue = Math.max(0, dueTotalAmount + otherFee - discount);

            totalDueInput.value = totalDue.toFixed(2);
            remainingDueAmountInput.value = Math.max(0, totalDue - paidAmount).toFixed(2);

            return { otherFee, discount, paidAmount, totalDue };
        };

        closeBtn.onclick = () => {
            modal._closeModal();
        };

        const needsTransactionId = () =>
            paymentMethodSelect.value !== '' && paymentMethodSelect.value !== 'Cash';

        const toggleTransactionId = () => {
            transactionIdWrapper.classList.toggle('hidden', ! needsTransactionId());

            if (! needsTransactionId()) {
                transactionIdInput.value = '';
            }
        };

        function resetPaymentProof() {
            paymentProofInput.value = '';
            paymentProofPreview.textContent = '';
        }

        paymentMethodSelect.onchange = toggleTransactionId;
        otherFeeInput.oninput = getSummary;
        discountInput.oninput = getSummary;
        paidAmountInput.oninput = getSummary;

        paymentProofInput.addEventListener('change', () => {
            const file = paymentProofInput.files?.[0];

            paymentProofPreview.textContent = file
                ? `${file.name} (${(file.size / 1024).toFixed(0)} KB)`
                : '';
        });

        submitBtn.onclick = async () => {
            const { otherFee, discount, paidAmount, totalDue } = getSummary();
            const paymentMethod = paymentMethodSelect.value;
            const transactionId = transactionIdInput.value.trim();
            const remark = document.getElementById('remark').value;

            if (! paidAmount || paidAmount <= 0) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please enter a valid paid amount.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            if (discount > dueTotalAmount + otherFee) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Discount amount cannot exceed the total due amount.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            if (! paymentMethod) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please select a payment method.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            if (needsTransactionId() && ! transactionId && ! paymentProofInput.files?.length) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please enter the transaction ID or upload the payment proof (screenshot).',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            if (paidAmount > totalDue) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Paid amount cannot exceed total due amount.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            const isConfirmed = await Swal.fire({
                title: 'Are you sure?',
                text: `You are about to pay ${paidAmount} for due amount.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Pay it!'
            });

            if (isConfirmed.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('paid_amount', paidAmount);
                    formData.append('other_fee', otherFee);
                    formData.append('discount_amount', discount);
                    formData.append('payment_method', paymentMethod);
                    formData.append('remark', remark || '');

                    if (transactionId) {
                        formData.append('transaction_id', transactionId);
                    }

                    const proofFile = paymentProofInput.files?.[0];

                    if (proofFile) {
                        formData.append('payment_proof', proofFile);
                    }

                    const response = await fetch(`/partial/paid/${saleId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                        },
                        body: formData,
                    });

                    const result = await response.json();
                    if (result.success) {
                        Swal.fire({
                            title: 'Payment Successful!',
                            text: 'Due amount has been successfully paid.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });

                        document.getElementById('paidAmountInput').value = '';
                        document.getElementById('otherFeeInput').value = '0.00';
                        discountInput.value = '0.00';
                        document.getElementById('remainingDueAmountInput').value = '';
                        paymentMethodSelect.value = '';
                        transactionIdInput.value = '';
                        transactionIdWrapper.classList.add('hidden');
                        resetPaymentProof();
                        document.getElementById('remark').value = '';
                        modal._closeModal();
                        getList();
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: result.message ||
                                'Failed to process payment. Please try again later.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                } catch (error) {
                    console.error('Error processing payment:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while processing the payment.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            }
        };
    }
</script>
