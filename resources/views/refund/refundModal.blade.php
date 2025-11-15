   <div id="refundModal" class="hidden fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50">
       <div class="bg-white p-6 rounded shadow-lg" style="width: 500px;"> <!-- Custom width -->
           <h2 class="text-lg font-semibold mb-4">Refund</h2>
           <div class="grid grid-cols-2 gap-5">
               <div>
                   <label class="block text-sm font-medium text-gray-700">Received Amount</label>
                   <input type="text" id="receivedAmountInput" class="border px-3 py-2 mb-4 w-full rounded" readonly
                       placeholder="Enter Received Amount" />
               </div>
               <div>
                   <label class="block text-sm font-medium text-gray-700">Refund Amount</label>
                   <input type="number" id="refundAmountInput" class="border px-3 py-2 mb-4 w-full rounded"
                       placeholder="Enter Refunded Amount" />
               </div>

           </div>

           <div class="grid grid-cols-2 gap-5">
               <div>
                   <label class="block text-sm font-medium text-gray-700">Purchase Number of Ticket</label>
                   <input type="text" id="PurchaseTicketInput" class="border px-3 py-2 mb-4 w-full rounded" readonly
                       placeholder="Enter Purchase Number of Ticket" />
               </div>
               <div>
                   <label class="block text-sm font-medium text-gray-700">Refund Number Of Ticket</label>
                   <input type="text" id="refundTicketInput" class="border px-3 py-2 mb-4 w-full rounded"
                       placeholder="Enter Refund Number Of Ticket" />
               </div>

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
               <button id="submitShipmentBtn" class="bg-blue-500 text-white px-4 py-2 rounded">Refund</button>
               <button id="closeModalBtn" class="bg-gray-400 text-white px-4 py-2 ml-2 rounded">Cancel</button>
           </div>
       </div>
   </div>


   <script>
       function refunded(btn, getList) {
           const saleId = btn.dataset.id;
           const status = btn.dataset.status;
           const received_total_amount = btn.dataset.received_total_amount;
           const number_ticket = btn.dataset.number_ticket;
           document.getElementById('receivedAmountInput').value = received_total_amount;
           document.getElementById('PurchaseTicketInput').value = number_ticket;

           // Show the modal
           const modal = document.getElementById('refundModal');
           modal.classList.remove('hidden');

           // Close modal event
           document.getElementById('closeModalBtn').addEventListener('click', () => {
               modal.classList.add('hidden');
           });

           // Submit shipment event
           document.getElementById('submitShipmentBtn').addEventListener('click', async () => {
               const refundAmountInput = document.getElementById('refundAmountInput').value;
               const refundTicketInput = document.getElementById('refundTicketInput').value;
               const remarkInput = document.getElementById('remark').value;

               if (!refundAmountInput) {
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

               // Proceed with the verification process
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
                       const response = await fetch(`/partial/refund/${saleId}`, {
                           method: 'POST',
                           headers: {
                               'Content-Type': 'application/json',
                               'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                   .getAttribute('content'),
                           },
                           body: JSON.stringify({
                               refunded_amount: refundAmountInput,
                               refunded_number_of_tickets: refundTicketInput,
                               remark: remarkInput,
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

                           document.getElementById('refundAmountInput').value = '';
                           document.getElementById('refundTicketInput').value = '';
                           getList(); // Assuming this refreshes or reloads the list
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
