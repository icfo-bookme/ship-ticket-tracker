<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div
        class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white h-full max-h-[90%] py-5 overflow-y-auto">
        <div class="mt-3 h-full">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-xl font-semibold text-gray-900">View Sale Details</h3>
                <button id="closeModalX" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div id="viewForm" class="mt-4">
                <input type="hidden" id="editId">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Customer Information -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Customer Name</label>
                        <div id="editCustomerName"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Customer Mobile</label>
                        <div id="editMobile"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">NID</label>
                        <div id="editnid"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <div id="editEmail"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <!-- Sales Source -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Sales Source</label>
                        <div id="editSalesSource"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <!-- Ship Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Ship</label>
                        <div id="editShip"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <!-- Journey Date -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Journey Date</label>
                        <div id="editJourneyDate"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Return Date</label>
                        <div id="editReturnDate"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <!-- Ticket Fee -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Ticket Fee</label>
                        <div id="editTicketFee"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Number Of Tickets</label>
                        <div id="editTicketNumber"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <!-- Payment Information -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                        <div id="editPaymentMethod"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Received Amount</label>
                        <div id="editReceivedAmount"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Due Amount</label>
                        <div id="editDueAmount"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <!-- Company -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Company</label>
                        <div id="editCompany"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <!-- Issued Date -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Issued Date</label>
                        <div id="editIssuedDate"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <!-- Sold By -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Sold By</label>
                        <div id="editSoldBy"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <!-- Status -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <div id="editStatus"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Ticket Category</label>
                        <div id="editTicketCategory"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 bg-gray-50"></div>
                    </div>
                </div>

                <div class="flex justify-end py-5 space-x-3">
                    <button type="button" id="cancelBtn"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function showModal(btn, modal) {

        const statusDisplay = formatStatus(btn.dataset.status);
        const salesSourceDisplay = formatSalesSource(btn.dataset.source);

        const fields = {
            'editId': btn.dataset.id,
            'editCustomerName': btn.dataset.customer || 'Not provided',
            'editnid': btn.dataset.nid || 'Not provided',
            'editMobile': btn.dataset.mobile || 'Not provided',
            'editEmail': btn.dataset.email || 'Not provided',
            'editSalesSource': salesSourceDisplay,
            'editShip': btn.dataset.ship - name || 'Not specified',
            'editJourneyDate': formatDateForDisplay(btn.dataset.journeydate),
            'editReturnDate': formatDateForDisplay(btn.dataset.returndate),
            'editTicketFee': btn.dataset.ticketfee ? `$${parseFloat(btn.dataset.ticketfee).toFixed(2)}` : 'Not specified',
            'editTicketNumber': btn.dataset.number_of_ticket || 'Not specified',
            'editPaymentMethod': btn.dataset.payment_method || 'Not specified',
            'editReceivedAmount': btn.dataset.receivedamount ?
                `$${parseFloat(btn.dataset.receivedamount).toFixed(2)}` : '$0.00',
            'editDueAmount': btn.dataset.dueamount ? `$${parseFloat(btn.dataset.dueamount).toFixed(2)}` : '$0.00',
            'editCompany': btn.dataset.company - name || 'Not specified',
            'editIssuedDate': formatDateForDisplay(btn.dataset.issueddate),
            'editSoldBy': btn.dataset.soldby || 'Not specified',
            'editStatus': statusDisplay,
            'editTicketCategory': btn.dataset.ticket_category || 'Not specified'
        };

        Object.keys(fields).forEach(fieldId => {
            const element = document.getElementById(fieldId);
            if (element) {
                element.textContent = fields[fieldId] || 'Not specified';
            }
        });

        modal.show();
    }



    function formatStatus(status) {
        const statusMap = {
            'pending': 'Pending',
            'payment-verified': 'Payment Verified',
            'ticket-issued': 'Ticket Issued',
            'ticket-printed': 'Ticket Printed',
            'shipment_id_entered': 'Shipment ID Entered',
            'shipped': 'Shipped'
        };
        return statusMap[status] || status;
    }

    function formatSalesSource(source) {
        const sourceMap = {
            'WhatsApp(016)': 'WhatsApp (016)',
            'WhatsApp(018)': 'WhatsApp (018)',
            'WhatsApp(019)': 'WhatsApp (019)',
            'Facebook': 'Facebook',
            'Messenger': 'Messenger',
            'Walk-in': 'Walk-in',
            'Others': 'Others'
        };
        return sourceMap[source] || source;
    }

    function formatDateForDisplay(dateString) {
        if (!dateString || dateString === 'Not specified') return 'Not specified';

        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Invalid date';

        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
</script>