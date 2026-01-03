    <!-- Modal Structure -->
    <div id="shipmentModal" class="hidden fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50 z-50">
        <div class="bg-white p-6 rounded shadow-lg w-96">
            <h2 class="text-lg font-semibold mb-4">Enter Shipment ID</h2>
            <input type="text" id="shipmentIdInput" class="border px-3 py-2 mb-4 w-full rounded"
                placeholder="Enter shipment ID" />
            <div class="flex justify-end">
                <button id="submitShipmentBtn" class="bg-blue-500 text-white px-4 py-2 rounded">Entry</button>
                <button id="close1ModalBtn" class="bg-gray-400 text-white px-4 py-2 ml-2 rounded">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        function varifyShipment(btn, getList) {
            const saleId = btn.dataset.id;
            const status = btn.dataset.status;

            // Show the modal
            const modal = document.getElementById('shipmentModal');
            modal.classList.remove('hidden');


            const closeBtn = document.getElementById('close1ModalBtn');
            const submitBtn = document.getElementById('submitShipmentBtn');

            const newCloseBtn = closeBtn.cloneNode(true);
            const newSubmitBtn = submitBtn.cloneNode(true);

            closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);
            submitBtn.parentNode.replaceChild(newSubmitBtn, submitBtn);

            // Close modal event
            newCloseBtn.addEventListener('click', () => {
                modal.classList.add('hidden');
                document.getElementById('shipmentIdInput').value = '';
            });

            // Submit shipment event
            newSubmitBtn.addEventListener('click', async () => {
                const shipmentId = document.getElementById('shipmentIdInput').value;

                if (!shipmentId) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Please enter a shipment ID.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-red-600 text-white'
                        }
                    });
                    return;
                }

                // Close modal after submission
                modal.classList.add('hidden');
                document.getElementById('shipmentIdInput').value = '';

                // Rest of your existing code...
                const isConfirmed = await Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Verify it!',
                    customClass: {
                        confirmButton: 'bg-blue-950 text-white',
                        cancelButton: 'bg-red-500 text-white'
                    }
                });

                if (isConfirmed.isConfirmed) {
                    try {
                        const response = await fetch(`/sale/verify/${saleId}/${status}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                            },
                            body: JSON.stringify({
                                shipmentId
                            })
                        });

                        const result = await response.json();
                        if (result.success) {
                            Swal.fire({
                                title: 'Verified!',
                                text: 'Shipment has been successfully verified.',
                                icon: 'success',
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'bg-blue-950 text-white'
                                }
                            });
                            getList();
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to verify shipment. Please try again later.',
                                icon: 'error',
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'bg-red-600 text-white'
                                }
                            });
                        }
                    } catch (error) {
                        console.error('Error verifying shipment:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while verifying the shipment.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'bg-red-600 text-white'
                            }
                        });
                    }
                }
            });
        }
    </script>
