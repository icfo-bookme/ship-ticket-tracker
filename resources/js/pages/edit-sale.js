const configElement = document.getElementById('editSaleConfig');
const config = {
    passengerCount: Number(configElement?.dataset.passengerCount || 0),
    paymentCount: Number(configElement?.dataset.paymentCount || 0),
    whatsappNumber: configElement?.dataset.whatsapp || 'whatsapp',
    existingPdfCount: Number(configElement?.dataset.existingPdfCount || 0),
    pdfIndex: Number(configElement?.dataset.pdfIndex || 0),
    paymentVerified: configElement?.dataset.paymentVerified === 'true',
    maximumBirthDate: configElement?.dataset.maximumBirthDate || '',
};

document.addEventListener('DOMContentLoaded', function() {
            // Add co-passenger functionality
            let passengerIndex = config.passengerCount;
            let paymentIndex = config.paymentCount;

            // For additional PDF fields
            let additionalPdfIndex = 0;
            const whatsappNumber = config.whatsappNumber;
            const existingPdfCount = config.existingPdfCount;
            let currentPdfNumber = existingPdfCount + 1;

            document.getElementById('add-passenger').addEventListener('click', function() {
                const container = document.getElementById('co-passengers-container');
                const newPassenger = document.createElement('div');
                newPassenger.className =
                    'co-passenger-item bg-white rounded-lg p-3 shadow-sm border border-blue-200 hover:shadow-md transition duration-200 ease-in-out';
                newPassenger.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-semibold text-gray-700">Name</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="co_passengers[${passengerIndex}][name]" title="Copy Passenger Name">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="text" name="co_passengers[${passengerIndex}][name]" 
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-semibold text-gray-700">NID</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="co_passengers[${passengerIndex}][nid]" title="Copy Passenger NID">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="text" name="co_passengers[${passengerIndex}][nid]" 
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-semibold text-gray-700">Mobile Number</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="co_passengers[${passengerIndex}][co_passernger_number]" title="Copy Passenger Mobile">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="text" name="co_passengers[${passengerIndex}][co_passernger_number]" 
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-semibold text-gray-700">Date of Birth</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="co_passengers[${passengerIndex}][date_of_birth]" title="Copy Passenger Date of Birth">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="date" name="co_passengers[${passengerIndex}][date_of_birth]" 
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                        </div>
                    </div>
                    <button type="button" class="mt-3 bg-red-500 hover:bg-red-600 text-white py-1.5 px-2.5 rounded-lg text-sm font-semibold transition duration-200 ease-in-out transform hover:scale-105 remove-passenger">
                        <i class="fas fa-user-times mr-1"></i>Remove Passenger
                    </button>
                `;
                container.appendChild(newPassenger);
                passengerIndex++;
            });

            // Add payment functionality
            document.getElementById('add-payment').addEventListener('click', function() {
                const container = document.getElementById('payments-container');
                const newPayment = document.createElement('div');
                newPayment.className =
                    'payment-item bg-white rounded-lg p-3 shadow-sm border border-blue-200 hover:shadow-md transition duration-200 ease-in-out';
                newPayment.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-semibold text-gray-700">Payment Method *</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="payments[${paymentIndex}][payment_method]" title="Copy Payment Method">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <select name="payments[${paymentIndex}][payment_method]" required
                                    class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                <option value="">Select Method</option>
                                <option value="Cash">Cash</option>
                                <option value="Bkash">Bkash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Card">Card</option>
                            </select>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-semibold text-gray-700">Amount *</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="payments[${paymentIndex}][received_amount]" title="Copy Amount">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <div class="flex items-center">
                                <span class="text-gray-500 mr-2">৳</span>
                                <input type="number" step="0.01" name="payments[${paymentIndex}][received_amount]" required
                                       class="copyable-field payment-amount w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-semibold text-gray-700">Transaction ID</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="payments[${paymentIndex}][transaction_id]" title="Copy Transaction ID">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="text" name="payments[${paymentIndex}][transaction_id]"
                                   placeholder="TRX-123456"
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-semibold text-gray-700">Payment Date & Time *</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="payments[${paymentIndex}][payment_datetime]" title="Copy Payment Date & Time">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="datetime-local" name="payments[${paymentIndex}][payment_datetime]" required
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-semibold text-gray-700">Remark</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="payments[${paymentIndex}][remark]" title="Copy Remark">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="text" name="payments[${paymentIndex}][remark]"
                                   placeholder="Optional note"
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-semibold text-gray-700">Payment Proof</label>
                            </div>
                            <input type="file" name="payments[${paymentIndex}][proof_file]"
                                   accept="image/*,application/pdf"
                                   class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 py-1.5 px-2.5 text-xs">
                        </div>
                    </div>
                    <div class="flex justify-end mt-2">
                        <button type="button" class="bg-red-500 hover:bg-red-600 text-white py-1.5 px-2.5 rounded-lg text-sm font-semibold transition duration-200 ease-in-out transform hover:scale-105 remove-payment">
                            <i class="fas fa-trash mr-1"></i>Remove Payment
                        </button>
                    </div>
                `;
                container.appendChild(newPayment);
                paymentIndex++;
                updatePaymentCount();

                // Set current datetime for the new payment only
                const datetimeInput = newPayment.querySelector('input[type="datetime-local"]');
                const now = new Date();
                const timezoneOffset = now.getTimezoneOffset() * 60000;
                const localISOTime = new Date(now - timezoneOffset).toISOString().slice(0, 16);
                datetimeInput.value = localISOTime;

                // Trigger calculation for new payment
                calculateFinancials();
            });

            // Add additional PDF field functionality
            const addAdditionalPdfBtn = document.getElementById('add-additional-pdf');
            if (addAdditionalPdfBtn) {
                addAdditionalPdfBtn.addEventListener('click', function() {
                    const container = document.getElementById('additional-pdf-fields');
                    const newPdfField = document.createElement('div');
                    newPdfField.className =
                        'additional-pdf-item bg-white rounded-lg p-4 shadow-sm border border-yellow-200 hover:shadow-md transition duration-200 ease-in-out';
                    newPdfField.innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-sm font-semibold text-gray-700">
                                        Additional PDF-${additionalPdfIndex + 1} *
                                    </label>
                                    <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="additional_pdf_${additionalPdfIndex}" title="Copy PDF Name">
                                        <i class="fas fa-copy text-xs"></i>
                                    </button>
                                </div>
                                <input type="text" 
                                       name="additional_pdf[${additionalPdfIndex}]" 
                                       id="additional_pdf_${additionalPdfIndex}"
                                       value="${whatsappNumber}-${currentPdfNumber}.pdf"
                                       readonly
                                       class="copyable-field w-full border-gray-300 rounded-lg shadow-sm py-1.5 px-2.5 bg-gray-50">
                            </div>
                            <div class="flex items-end">
                                <button type="button" class="bg-red-500 hover:bg-red-600 text-white py-1.5 px-2.5 rounded-lg text-sm font-semibold transition duration-200 ease-in-out transform hover:scale-105 remove-additional-pdf">
                                    <i class="fas fa-trash mr-1"></i>Remove Field
                                </button>
                            </div>
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Filename: ${whatsappNumber}-${currentPdfNumber}.pdf
                        </div>
                    `;
                    container.appendChild(newPdfField);
                    additionalPdfIndex++;
                    currentPdfNumber++;
                });
            }

            // Remove additional PDF field
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-additional-pdf')) {
                    e.target.closest('.additional-pdf-item').remove();
                    // Don't decrement currentPdfNumber to maintain sequence
                }
            });

            // Calculate all financial values
            function calculateFinancials() {
                // Calculate total received amount from all payments
                let totalReceived = 0;
                document.querySelectorAll('.payment-amount').forEach(input => {
                    const value = parseFloat(input.value) || 0;
                    totalReceived += value;
                });

                // Update received amount field
                const receivedAmountInput = document.getElementById('received_amount');
                receivedAmountInput.value = totalReceived.toFixed(2);

                // Get ticket fee, other fee and discount
                const ticketFee = parseFloat(document.getElementById('ticket_fee').value) || 0;
                const otherFee = parseFloat(document.getElementById('other_fee').value) || 0;
                const discountAmount = parseFloat(document.getElementById('discount_amount').value) || 0;

                validateDiscountAmount();

                // Calculate total payable (ticket fee + other fee - discount)
                const totalPayable = Math.max(0, ticketFee + otherFee - discountAmount);
                document.getElementById('total_payable').value = totalPayable.toFixed(2);

                const extraReceivedAmount = Math.max(0, totalReceived - totalPayable);
                document.getElementById('extra_received_amount').value = extraReceivedAmount.toFixed(2);

                // Calculate due amount (total payable - total received)
                const dueAmount = totalPayable - totalReceived;
                document.getElementById('due_amount').value = dueAmount.toFixed(2);

                // Update payment count
                updatePaymentCount();
            }

            function validateDiscountAmount() {
                const ticketFeeInput = document.getElementById('ticket_fee');
                const discountInput = document.getElementById('discount_amount');
                const error = document.getElementById('discount-error');
                const ticketFee = parseFloat(ticketFeeInput.value) || 0;
                const discount = parseFloat(discountInput.value) || 0;
                const invalid = discount > ticketFee;

                discountInput.max = ticketFee.toFixed(2);
                discountInput.setCustomValidity(invalid ? 'Discount Amount cannot exceed Total Ticket Fee.' : '');
                error.textContent = invalid ? 'Discount Amount cannot exceed Total Ticket Fee.' : '';
                error.classList.toggle('hidden', !invalid);
                discountInput.classList.toggle('border-red-500', invalid);

                return !invalid;
            }

            // Update payment count
            function updatePaymentCount() {
                const paymentCount = document.querySelectorAll('.payment-item').length;
                document.getElementById('total-payment-count').textContent = paymentCount;
            }

            // Event listeners for financial calculations
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('payment-amount') ||
                    e.target.id === 'ticket_fee' ||
                    e.target.id === 'other_fee' ||
                    e.target.id === 'discount_amount') {
                    calculateFinancials();
                }
            });

            document.getElementById('ticketForm')?.addEventListener('submit', function(event) {
                if (!validateDiscountAmount()) {
                    event.preventDefault();
                    document.getElementById('discount_amount').focus();
                }
            });

            // Remove functionality
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-passenger')) {
                    e.target.closest('.co-passenger-item').remove();
                }
                if (e.target.classList.contains('remove-payment')) {
                    e.target.closest('.payment-item').remove();
                    calculateFinancials();
                }
            });

            // Copy functionality
            function showToast(message) {
                const toast = document.getElementById('copyToast');
                const toastMessage = document.getElementById('toastMessage');
                toastMessage.textContent = message;
                toast.classList.remove('translate-y-full');

                setTimeout(() => {
                    toast.classList.add('translate-y-full');
                }, 3000);
            }

            function markCopiedPdf(field) {
                const pdfItem = field.closest('.existing-pdf-item, .pdf-item, .additional-pdf-item');

                if (!pdfItem) {
                    return;
                }

                pdfItem.classList.remove('bg-white', 'border-blue-200', 'border-yellow-200');
                pdfItem.classList.add('bg-red-600', 'border-red-600', 'text-white');
                pdfItem.style.backgroundColor = '#dc2626';
                pdfItem.style.borderColor = '#dc2626';
                pdfItem.querySelectorAll('label, .text-gray-500, .text-gray-700').forEach((element) => {
                    element.classList.add('text-white');
                });

                field.classList.remove('bg-gray-50');
                field.classList.add('bg-red-600', 'text-white');
                field.style.backgroundColor = '#dc2626';
                field.style.color = '#ffffff';
            }

            function copyTextToClipboard(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    return navigator.clipboard.writeText(text);
                }

                return new Promise((resolve, reject) => {
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    textArea.setAttribute('readonly', '');
                    textArea.style.position = 'fixed';
                    textArea.style.top = '-9999px';
                    textArea.style.left = '-9999px';
                    document.body.appendChild(textArea);
                    textArea.select();

                    try {
                        const copied = document.execCommand('copy');
                        document.body.removeChild(textArea);

                        if (copied) {
                            resolve();
                        } else {
                            reject(new Error('Copy command was not successful.'));
                        }
                    } catch (error) {
                        document.body.removeChild(textArea);
                        reject(error);
                    }
                });
            }

            function getCopyFieldLabel(copyBtn, field) {
                const fieldWrapper = copyBtn.closest('.existing-pdf-item, .pdf-item, .additional-pdf-item') ||
                    copyBtn.closest('.flex')?.parentElement ||
                    copyBtn.closest('div');
                const label = fieldWrapper?.querySelector('label');

                return label?.textContent?.replace('*', '').trim() ||
                    copyBtn.getAttribute('title') ||
                    field?.getAttribute('name') ||
                    field?.id ||
                    'field';
            }

            // Copy individual field
            document.addEventListener('click', function(e) {
                if (e.target.closest('.copy-field-btn')) {
                    const copyBtn = e.target.closest('.copy-field-btn');
                    const fieldId = copyBtn.dataset.field;

                    // Find the corresponding input/select/textarea field
                    let field;
                    if (fieldId.includes('[') && fieldId.includes(']')) {
                        // Handle array fields like payments[0][amount]
                        const fieldName = fieldId.replace(/\[(\d+)\]/g, '[$1]');
                        field = document.querySelector(`[name="${fieldName}"]`);
                    } else {
                        field = document.getElementById(fieldId);
                    }

                    if (field) {
                        let valueToCopy;

                        if (field.tagName === 'SELECT') {
                            valueToCopy = field.options[field.selectedIndex].text;
                        } else if (field.type === 'radio' || field.type === 'checkbox') {
                            if (field.checked) {
                                valueToCopy = field.nextElementSibling?.textContent?.trim() || field.value;
                            } else {
                                valueToCopy = '';
                            }
                        } else {
                            valueToCopy = field.value;
                        }

                        if (valueToCopy && valueToCopy.trim() !== '') {
                            copyTextToClipboard(valueToCopy).then(() => {
                                const fieldLabel = getCopyFieldLabel(copyBtn, field);
                                markCopiedPdf(field);
                                showToast(`Copied: ${fieldLabel}`);
                            }).catch(err => {
                                console.error('Failed to copy: ', err);
                                showToast('Failed to copy field');
                            });
                        } else {
                            showToast('No data to copy');
                        }
                    }
                }
            });

            // Initial calculation
            calculateFinancials();

            // Initialize PDF fields from payment-verified section (only if it exists)
            if (config.paymentVerified) {
                let pdfIndex = config.pdfIndex;
                const container = document.getElementById('pdf-fields');
                if (container && document.getElementById('addPdfField')) {
                    document.getElementById('addPdfField').addEventListener('click', () => {
                        pdfIndex++;

                        const div = document.createElement('div');
                        div.className = 'pdf-item mb-2 border p-3 rounded-lg relative';

                        div.innerHTML = `
                            <div class="flex items-center justify-between mb-1">
                                <label for="pdf-${pdfIndex}" class="text-sm font-semibold text-gray-100">
                                    Pdf-${pdfIndex}
                                </label>

                                <div class="flex gap-2">
                                    <button type="button"
                                        class="copy-field-btn text-blue-600"
                                        data-field="pdf-${pdfIndex}">
                                        <i class="fas fa-copy text-xs"></i>
                                    </button>

                                    <button type="button"
                                        class="remove-pdf-btn text-red-600">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <input
                                type="text"
                                id="pdf-${pdfIndex}"
                                name="pdf[${pdfIndex}]"
                                readonly
                                value="${whatsappNumber}-${pdfIndex}"
                                class="copyable-field w-full border-gray-300 rounded-lg py-1.5 px-2.5"
                            >
                        `;

                        container.appendChild(div);
                    });

                    container.addEventListener('click', (e) => {
                        if (e.target.closest('.remove-pdf-btn')) {
                            e.target.closest('.pdf-item').remove();
                        }
                    });
                }
            }
        });

document.addEventListener('DOMContentLoaded', () => {
            const collectFromOffice = document.getElementById('collect_from_office');
            const addressWrapper = document.getElementById('addressFieldWrapper');
            const address = document.getElementById('address');

            const updateAddressVisibility = () => {
                addressWrapper.classList.toggle('hidden', collectFromOffice.checked);
                address.required = !collectFromOffice.checked;
            };

            collectFromOffice.addEventListener('change', updateAddressVisibility);
            updateAddressVisibility();
        });

document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('ticketForm');
            const maximumBirthDate = config.maximumBirthDate;

            function validateBirthDate(input) {
                const invalid = input.value !== '' && input.value > maximumBirthDate;
                input.max = maximumBirthDate;
                input.setCustomValidity(invalid ? 'Passenger must be at least 18 years old.' : '');
                input.classList.toggle('border-red-500', invalid);

                let error = input.parentElement.querySelector('.birth-date-error');
                if (!error && invalid) {
                    error = document.createElement('p');
                    error.className = 'birth-date-error mt-1 text-sm text-red-600';
                    input.parentElement.appendChild(error);
                }

                if (error) {
                    error.textContent = invalid ? 'Passenger must be at least 18 years old.' : '';
                    error.classList.toggle('hidden', !invalid);
                }

                return !invalid;
            }

            form?.addEventListener('input', (event) => {
                if (event.target.matches('input[name="date_of_birth"], input[name^="co_passengers["][name$="][date_of_birth]"]')) {
                    validateBirthDate(event.target);
                }
            });

            form?.addEventListener('submit', (event) => {
                const valid = [...form.querySelectorAll('input[name="date_of_birth"], input[name^="co_passengers["][name$="][date_of_birth]"]')]
                    .map(validateBirthDate)
                    .every(Boolean);

                if (!valid) {
                    event.preventDefault();
                    form.querySelector('.border-red-500')?.focus();
                }
            });

            form?.querySelectorAll('input[name="date_of_birth"], input[name^="co_passengers["][name$="][date_of_birth]"]')
                .forEach(validateBirthDate);
        });
