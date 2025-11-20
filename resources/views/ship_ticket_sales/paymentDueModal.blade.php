<div id="dueModal" class="hidden fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50">
    <div class="bg-white p-6 rounded shadow-lg" style="width: 500px;">
        <h2 class="text-lg font-semibold mb-4">Paid Due Amount</h2>
        <div class="grid grid-cols-2 gap-5">
            <div>
                <label class="block text-sm font-medium text-gray-700">Total Due Amount</label>
                <input type="text" id="dueAmountInput" class="border px-3 py-2 mb-4 w-full rounded" readonly
                    placeholder="Enter due Amount" />
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
      
        <div class="mt-4">
            <label for="remark" class="block text-sm font-medium text-gray-700 mb-2">
                Remark
            </label>
            <textarea id="remark" name="remark" rows="3" placeholder="Enter remark (optional)"
                class="border border-gray-300 dark:border-gray-600 dark:bg-gray-700 
            dark:text-white rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-blue-500 transition"></textarea>
        </div>

        <div class="flex justify-end">
            <button id="submitPaymentBtn" class="bg-blue-500 text-white px-4 py-2 rounded">Pay Due</button>
            <button id="closeModalBtn" class="bg-gray-400 text-white px-4 py-2 ml-2 rounded">Cancel</button>
        </div>
    </div>
</div>

<script>
    function due(btn, getList) {
        const saleId = btn.dataset.id;
        const due_total_amount = btn.dataset.due_amount;
        document.getElementById('dueAmountInput').value = due_total_amount;

        // Reset form
        document.getElementById('paidAmountInput').value = '';
        document.getElementById('remainingDueAmountInput').value = due_total_amount;
        document.getElementById('paymentMethodSelect').value = '';
        document.getElementById('remark').value = '';

        // Show the modal
        const modal = document.getElementById('dueModal');
        modal.classList.remove('hidden');

        // Remove previous event listeners by using onclick instead
        const closeBtn = document.getElementById('closeModalBtn');
        const submitBtn = document.getElementById('submitPaymentBtn');
        const paidAmountInput = document.getElementById('paidAmountInput');

        // Close modal event - SIMPLE FIX
        closeBtn.onclick = () => {
            modal.classList.add('hidden');
        };

        // Auto-calculate remaining due amount when paid amount changes
        paidAmountInput.oninput = function() {
            const paidAmount = parseFloat(this.value) || 0;
            const totalDue = parseFloat(due_total_amount);
            const remainingDue = totalDue - paidAmount;
            
            document.getElementById('remainingDueAmountInput').value = remainingDue.toFixed(2);
        };

        // Submit payment event
        submitBtn.onclick = async () => {
            const paidAmount = document.getElementById('paidAmountInput').value;
            const paymentMethod = document.getElementById('paymentMethodSelect').value;
            const remark = document.getElementById('remark').value;

            // Validation
            if (!paidAmount || paidAmount <= 0) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please enter a valid paid amount.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            if (!paymentMethod) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Please select a payment method.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Check if paid amount exceeds due amount
            if (parseFloat(paidAmount) > parseFloat(due_total_amount)) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Paid amount cannot exceed due amount.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Confirmation dialog
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
                    const response = await fetch(`/partial/paid/${saleId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                        },
                        body: JSON.stringify({
                            paid_amount: paidAmount,
                            payment_method: paymentMethod,
                            remark: remark,
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        Swal.fire({
                            title: 'Payment Successful!',
                            text: 'Due amount has been successfully paid.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });

                        // Clear form
                        document.getElementById('paidAmountInput').value = '';
                        document.getElementById('remainingDueAmountInput').value = '';
                        document.getElementById('paymentMethodSelect').value = '';
                        document.getElementById('remark').value = '';
                        
                        // Close modal
                        modal.classList.add('hidden');
                        
                        // Refresh list
                        getList();
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: result.message || 'Failed to process payment. Please try again later.',
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

    // Add click outside to close modal
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('dueModal');
        if (event.target === modal) {
            modal.classList.add('hidden');
        }
    });
</script>