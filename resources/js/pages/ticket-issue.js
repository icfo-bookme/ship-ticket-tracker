document.addEventListener('DOMContentLoaded', function() {
            const configElement = document.getElementById('ticketIssueConfig');
            const config = {
                whatsappNumber: configElement?.dataset.whatsapp || 'whatsapp',
                currentPdfNumber: Number(configElement?.dataset.currentPdfNumber || 1),
                pdfIndex: Number(configElement?.dataset.pdfIndex || 0),
                paymentVerified: configElement?.dataset.paymentVerified === 'true',
            };
            // For additional PDF fields
            let additionalPdfIndex = 0;
            const whatsappNumber = config.whatsappNumber;
            let currentPdfNumber = config.currentPdfNumber;

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
            const form = document.getElementById('ticketForm');
            const allowedNames = new Set(['group_tickets', 'group_by_id', 'existing_pdf_action']);

            const existingPdfAction = form.querySelector('input[name="existing_pdf_action"]:checked');
            const submitButton = form.querySelector('button[type="submit"]');
            const existingPdfHelp = document.getElementById('existing-pdf-help');

            const updateExistingPdfAction = () => {
                const shouldContinue = form.querySelector('input[name="existing_pdf_action"]:checked')?.value === 'yes';

                if (submitButton && existingPdfAction) {
                    submitButton.disabled = !shouldContinue;
                    submitButton.classList.toggle('opacity-50', !shouldContinue);
                    submitButton.classList.toggle('cursor-not-allowed', !shouldContinue);
                }

                if (existingPdfHelp) {
                    existingPdfHelp.classList.toggle('hidden', shouldContinue);
                }
            };

            form.querySelectorAll('input[name="existing_pdf_action"]').forEach((input) => {
                input.addEventListener('change', updateExistingPdfAction);
            });

            updateExistingPdfAction();

            form.querySelectorAll('input, select, textarea').forEach((field) => {
                const isPdfField = field.name.startsWith('pdf[') || field.name.startsWith('additional_pdf[');

                if (isPdfField || allowedNames.has(field.name) || field.type === 'hidden') {
                    return;
                }

                if (field.matches('textarea') || (field.matches('input') && ['text', 'email', 'date', 'number', 'tel', 'url', 'search'].includes(field.type))) {
                    field.readOnly = true;
                } else {
                    field.disabled = true;
                }
            });

            form.querySelectorAll('button[type="button"]').forEach((button) => {
                const isAllowedAction = button.id === 'addPdfField' || button.id === 'add-additional-pdf' ||
                    button.classList.contains('copy-field-btn');

                if (!isAllowedAction) {
                    button.disabled = true;
                    button.classList.add('opacity-50', 'cursor-not-allowed');
                }
            });
        });
