 <!-- Edit Modal -->
 <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
     <div
         class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white h-full max-h-[90%] py-5 overflow-y-auto">
         <div class="mt-3 h-full">
             <div class="flex justify-between items-center pb-3 border-b">
                 <h3 class="text-xl font-semibold text-gray-900">Edit Sale</h3>
                 <button id="closeModalX" class="text-gray-400 hover:text-gray-600">
                     <i class="fas fa-times"></i>
                 </button>
             </div>

             <form id="editForm" class="mt-4">
                 <input type="hidden" id="editId">

                 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                     <!-- Customer Information -->
                     <div class="mb-4">
                         <label for="editCustomerName" class="block text-sm font-medium text-gray-700">Customer
                             Name</label>
                         <input type="text" id="editCustomerName"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>

                     <div class="mb-4">
                         <label for="editMobile" class="block text-sm font-medium text-gray-700">Customer Mobile</label>
                         <input type="text" id="editMobile"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>

                     <div class="mb-4">
                         <label for="editnid" class="block text-sm font-medium text-gray-700">NID</label>
                         <input type="text" id="editnid"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>

                     <div class="mb-4">
                         <label for="editEmail" class="block text-sm font-medium text-gray-700">Email</label>
                         <input type="text" id="editEmail"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>

                     <!-- Sales Source (Changed to dropdown) -->
                     <div class="mb-4">
                         <label for="editSalesSource" class="block text-sm font-medium text-gray-700">Sales
                             Source</label>
                         <select id="editSalesSource"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                             <option value="">Select source</option>

                             <option value="WhatsApp(016)">WhatsApp(016)</option>
                             <option value="WhatsApp(018)">WhatsApp(018)</option>
                             <option value="WhatsApp(019)">WhatsApp(019)</option>
                             <option value="Facebook">Facebook</option>
                             <option value="Messenger">Messenger</option>
                             <option value="Walk-in">Walk-in</option>
                             <option value="Others">Others</option>
                         </select>
                     </div>

                     <!-- Ship Selection -->
                     <div class="mb-4">
                         <label for="editShip" class="block text-sm font-medium text-gray-700">Ship</label>
                         <select id="editShip"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                             <option value="">Select Ship</option>
                             <!-- Options will be populated dynamically -->
                         </select>
                     </div>

                     <!-- Journey Date -->
                     <div class="mb-4">
                         <label for="editJourneyDate" class="block text-sm font-medium text-gray-700">Journey
                             Date</label>
                         <input type="date" id="editJourneyDate"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>

                     <div class="mb-4">
                         <label for="editReturnDate" class="block text-sm font-medium text-gray-700">Return Date</label>
                         <input type="date" id="editReturnDate"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>

                     <!-- Ticket Fee -->
                     <div class="mb-4">
                         <label for="editTicketFee" class="block text-sm font-medium text-gray-700">Ticket Fee</label>
                         <input type="number" step="0.01" id="editTicketFee"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>

                     <div class="mb-4">
                         <label for="editTicketNumber" class="block text-sm font-medium text-gray-700">Number Of
                             Tickets</label>
                         <input type="number" step="1" id="editTicketNumber"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>

                     <!-- Payment Information -->
                     <div class="mb-4">
                         <label for="editPaymentMethod" class="block text-sm font-medium text-gray-700">Payment
                             Method</label>
                         <select id="editPaymentMethod"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                             <option value="Cash">Cash</option>
                             <option value="Bkash">Bkash</option>
                             <option value="Nagad">Nagad</option>
                             <option value="Bank Transfer">Bank Transfer</option>
                         </select>
                     </div>

                     <div class="mb-4">
                         <label for="editReceivedAmount" class="block text-sm font-medium text-gray-700">Received
                             Amount</label>
                         <input type="number" step="0.01" id="editReceivedAmount"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>

                     <div class="mb-4">
                         <label for="editDueAmount" class="block text-sm font-medium text-gray-700">Due Amount</label>
                         <input type="number" step="0.01" id="editDueAmount"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>

                     <!-- Company -->
                     <div class="mb-4">
                         <label for="editCompany" class="block text-sm font-medium text-gray-700">Company</label>
                         <select id="editCompany"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                             <option value="">Select Company</option>
                             <!-- Options will be populated dynamically -->
                         </select>
                     </div>

                     <!-- Issued Date -->
                     <div class="mb-4">
                         <label for="editIssuedDate" class="block text-sm font-medium text-gray-700">Issued
                             Date</label>
                         <input type="date" id="editIssuedDate"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>

                     <!-- Sold By -->
                     <div class="mb-4">
                         <label for="editSoldBy" class="block text-sm font-medium text-gray-700">Sold By</label>
                         <input type="text" id="editSoldBy"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>

                     <!-- Status -->
                     <div class="mb-4">
                         <label for="editStatus" class="block text-sm font-medium text-gray-700">Status</label>
                         <select id="editStatus"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                             <option value="pending">Pending</option>
                             <option value="payment-verified">payment-verified</option>
                             <option value="ticket-issued">ticket-issued</option>
                             <option value="ticket-printed">ticket-printed</option>
                         </select>
                     </div>

                     <div class="mb-4">
                         <label for="editTicketCategory" class="block text-sm font-medium text-gray-700">Ticket
                             Category</label>
                         <input type="text" id="editTicketCategory"
                             class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2">
                     </div>
                 </div>

                 <div class="flex justify-end py-5 space-x-3">
                     <button type="button" id="cancelBtn"
                         class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                         Cancel
                     </button>
                     <button type="submit" class="px-4 py-2 bg-blue-950 text-white rounded-md hover:bg-blue-800">
                         Update Sale
                     </button>
                 </div>
             </form>
         </div>
     </div>
 </div>


 <script>
     function showEditModal(btn, getList, companies, ships) {

         populateEditShipDropdown();
         populateEditCompanyDropdown();

         const fields = {
             'editId': btn.dataset.id,
             'editCustomerName': btn.dataset.customer,
             'editnid': btn.dataset.nid,
             'editMobile': btn.dataset.mobile,
             'editEmail': btn.dataset.email,
             'editSalesSource': btn.dataset.source,
             'editShip': btn.dataset.ship,
             'editJourneyDate': formatDateForInput(btn.dataset.journeydate),
             'editReturnDate': formatDateForInput(btn.dataset.returndate),
             'editTicketFee': btn.dataset.ticketfee,
             'editTicketNumber': btn.dataset.number_of_ticket,
             'editPaymentMethod': btn.dataset.payment_method,
             'editReceivedAmount': btn.dataset.receivedamount,
             'editDueAmount': btn.dataset.dueamount,
             'editCompany': btn.dataset.companyid,
             'editIssuedDate': formatDateForInput(btn.dataset.issueddate),
             'editSoldBy': btn.dataset.soldby,
             'editStatus': btn.dataset.status,
             'editTicketCategory': btn.dataset.ticket_category
         };

         // Set values for all fields
         Object.keys(fields).forEach(fieldId => {
             const element = document.getElementById(fieldId);
             if (element) {
                 element.value = fields[fieldId] || '';
             }
         });

         // Show the edit modal
         modal.show();
     }

     function populateEditShipDropdown() {
         const editShipSelect = document.getElementById('editShip');
         if (!editShipSelect) return;

         editShipSelect.innerHTML = '<option value="">Select Ship</option>';
         ships.forEach(ship => {
             const option = document.createElement('option');
             option.value = ship.id;
             option.textContent = ship.name;
             editShipSelect.appendChild(option);
         });
     }

     function populateEditCompanyDropdown() {
         const editCompanySelect = document.getElementById('editCompany');
         if (!editCompanySelect) return;

         editCompanySelect.innerHTML = '<option value="">Select Company</option>';
         companies.forEach(company => {
             const option = document.createElement('option');
             option.value = company.id;
             option.textContent = company.name;
             editCompanySelect.appendChild(option);
         });
     }
     document.getElementById('editForm').addEventListener('submit', async (e) => {
         e.preventDefault();

         const id = document.getElementById('editId').value;
         const data = {
             customer_name: document.getElementById('editCustomerName').value,
             customer_mobile: document.getElementById('editMobile').value,
             nid: document.getElementById('editnid').value,
             email: document.getElementById('editEmail').value,
             sales_source: document.getElementById('editSalesSource').value,
             ship_id: document.getElementById('editShip').value,
             journey_date: document.getElementById('editJourneyDate').value,
             return_date: document.getElementById('editReturnDate').value,
             ticket_fee: document.getElementById('editTicketFee').value,
             number_of_ticket: document.getElementById('editTicketNumber').value,
             payment_method: document.getElementById('editPaymentMethod').value,
             received_amount: document.getElementById('editReceivedAmount').value,
             due_amount: document.getElementById('editDueAmount').value,
             company_id: document.getElementById('editCompany').value,
             issued_date: document.getElementById('editIssuedDate').value,
             sold_by: document.getElementById('editSoldBy').value,
             status: document.getElementById('editStatus').value,
             ticket_category: document.getElementById('editTicketCategory').value
         };

         await updateSale(id, data);
     });

     // Function to update sale
     async function updateSale(id, data) {
         try {
             const response = await fetch(`/sales/status/${id}`, {
                 method: 'PUT',
                 headers: {
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                 },
                 body: JSON.stringify(data)
             });

             const result = await response.json();

             if (response.ok) {
                 Swal.fire({
                     title: 'Success!',
                     text: 'Sale updated successfully!',
                     icon: 'success',
                     confirmButtonText: 'OK',
                     customClass: {
                         confirmButton: 'bg-blue-950 text-white'
                     }
                 });

                 getList();
                 modal.hide();
             } else {
                 throw new Error(result.message || 'Failed to update sale');
             }

         } catch (error) {
             console.error('Error updating sale:', error);
             Swal.fire({
                 title: 'Error!',
                 text: 'Failed to update sale. Please try again.',
                 icon: 'error',
                 confirmButtonText: 'OK',
                 customClass: {
                     confirmButton: 'bg-red-600 text-white'
                 }
             });
         }
     }
 </script>