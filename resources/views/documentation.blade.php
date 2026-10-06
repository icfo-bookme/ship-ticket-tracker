{{-- resources/views/documentation.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-base text-gray-800 leading-tight">
            Operations Guide
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <header class="border-b border-gray-300 pb-6">
                <p class="text-xs font-semibold uppercase text-blue-700">Ship Booking · Operations</p>
                <div class="mt-2 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900">Ship Booking User Guide</h1>
                        <p class="mt-2 max-w-3xl text-sm text-gray-600">
                            Daily workflows for ticket sales, payment verification, delivery, refunds, and financial review.
                        </p>
                    </div>
                    <span class="border-l-2 border-blue-600 pl-3 text-xs text-gray-500">For authorized system users</span>
                </div>
            </header>

            <details class="border-b border-gray-300 pb-4 lg:hidden">
                <summary class="cursor-pointer text-sm font-semibold text-gray-800">Guide contents</summary>
                <nav aria-label="Guide contents" class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:grid-cols-3">
                    <a class="text-gray-600 hover:text-blue-700" href="#manual-purpose">Overview</a>
                    <a class="text-gray-600 hover:text-blue-700" href="#navigation">Navigation</a>
                    <a class="text-gray-600 hover:text-blue-700" href="#sidebar-menus">Sidebar menus</a>
                    <a class="text-gray-600 hover:text-blue-700" href="#setup">Before tickets</a>
                    <a class="text-gray-600 hover:text-blue-700" href="#create-ticket">Create a ticket</a>
                    <a class="text-gray-600 hover:text-blue-700" href="#sales-workflow">Sales workflow</a>
                    <a class="text-gray-600 hover:text-blue-700" href="#due-payments">Due payments</a>
                    <a class="text-gray-600 hover:text-blue-700" href="#shipping">Parcel and shipment</a>
                    <a class="text-gray-600 hover:text-blue-700" href="#searching">Search and filters</a>
                    <a class="text-gray-600 hover:text-blue-700" href="#refunds">Refunds</a>
                    <a class="text-gray-600 hover:text-blue-700" href="#reports">Reports</a>
                    <a class="text-gray-600 hover:text-blue-700" href="#cash-collection">Available cash</a>
                    <a class="text-gray-600 hover:text-blue-700" href="#best-practices">Best practices</a>
                </nav>
            </details>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-[13rem_minmax(0,1fr)]">
                <aside class="hidden lg:block">
                    <nav aria-label="Guide contents" class="sticky top-20 border-l border-gray-300 pl-4">
                        <p class="mb-3 text-xs font-semibold uppercase text-gray-500">On this page</p>
                        <div class="space-y-2 text-sm">
                            <a class="block text-gray-600 hover:text-blue-700" href="#manual-purpose">Overview</a>
                            <a class="block text-gray-600 hover:text-blue-700" href="#navigation">Navigation</a>
                            <a class="block text-gray-600 hover:text-blue-700" href="#sidebar-menus">Sidebar menus</a>
                            <a class="block text-gray-600 hover:text-blue-700" href="#setup">Before tickets</a>
                            <a class="block text-gray-600 hover:text-blue-700" href="#create-ticket">Create a ticket</a>
                            <a class="block text-gray-600 hover:text-blue-700" href="#sales-workflow">Sales workflow</a>
                            <a class="block text-gray-600 hover:text-blue-700" href="#due-payments">Due payments</a>
                            <a class="block text-gray-600 hover:text-blue-700" href="#shipping">Parcel and shipment</a>
                            <a class="block text-gray-600 hover:text-blue-700" href="#searching">Search and filters</a>
                            <a class="block text-gray-600 hover:text-blue-700" href="#refunds">Refunds</a>
                            <a class="block text-gray-600 hover:text-blue-700" href="#reports">Reports</a>
                            <a class="block text-gray-600 hover:text-blue-700" href="#cash-collection">Available cash</a>
                            <a class="block text-gray-600 hover:text-blue-700" href="#best-practices">Best practices</a>
                        </div>
                    </nav>
                </aside>
                <main class="documentation-sections min-w-0">

            <div id="manual-purpose">
                <h2>1. Purpose of This Manual</h2>
                <p class="text-gray-700 mb-2">
                    This documentation explains how a user will operate the Ship Booking system from login to ticket
                    delivery. It is written for daily users who create tickets, verify payments, print tickets, collect
                    due payments, create parcels through Steadfast, process refunds, and check reports.
                </p>
                <p class="text-gray-700">
                    Follow the steps in order for the cleanest workflow. If a ticket is moved to the wrong status,
                    contact the system administrator before taking further action.
                </p>
            </div>

            <div id="navigation">
                <h2>2. Login and Basic Navigation</h2>
                <ol class="list-decimal list-inside text-gray-700 space-y-2">
                    <li>Open the application URL in a modern browser such as Chrome, Edge, or Firefox.</li>
                    <li>Enter your registered email and password, then click Login.</li>
                    <li>After login, use the left sidebar to open Sell, Refund, Create, Show Reports, WhatsApp, and other modules.</li>
                    <li>Use the top documentation link any time you need this guide.</li>
                    <li>After finishing work, open the user menu and log out to keep the account secure.</li>
                </ol>
            </div>

            <div id="sidebar-menus">
                <h2>3. Main Sidebar Menus</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="font-semibold text-gray-900 mb-1">Sell</p>
                        <p>Create Tickets, manage Sales by status, and configure Excel settings.</p>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="font-semibold text-gray-900 mb-1">Refund</p>
                        <p>Create refund requests and review refunded sales.</p>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="font-semibold text-gray-900 mb-1">Create</p>
                        <p>Add or update ships and companies before using them in ticket forms.</p>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="font-semibold text-gray-900 mb-1">Show Reports</p>
                        <p>Check sales reports and cash collection information for reconciliation.</p>
                    </div>
                </div>
            </div>

            <div id="setup">
                <h2>4. Before Creating Tickets</h2>
                <ul class="list-disc list-inside text-gray-700 space-y-2">
                    <li>Add the ship from Create > New Ship if it is not already available.</li>
                    <li>Add ticket packages/categories for the ship so users can select departure and return ticket types.</li>
                    <li>Add the source company from Create > New Company if the company is not listed.</li>
                    <li>Keep WhatsApp details updated from the WhatsApp menu if your team uses those numbers for customers.</li>
                </ul>
            </div>

            <div id="create-ticket">
                <h2>5. Create a New Ticket</h2>
                <p class="text-gray-700 mb-3">
                    Go to Sell > Create Tickets. Fill up the form carefully. Required fields are marked with a red star.
                </p>
                <ol class="list-decimal list-inside text-gray-700 space-y-2">
                    <li>Enter passenger name, mobile number, WhatsApp number, date of birth, NID, email, sales source, and company.</li>
                    <li>Select journey date, optional return date, and ship name.</li>
                    <li>Select departure ticket category and quantity. If return date is selected, fill return ticket category and quantity too.</li>
                    <li>Add one or more payment rows using Add Payment. Choose payment method, amount, paid date, and remark if needed.</li>
                    <li>Check ticket summary: total ticket count, ticket value, and other fee.</li>
                    <li>Check payment summary: Total Payable, Total Received, and Due Amount. These values are calculated from ticket and payment information.</li>
                    <li>Select BFTN Status if applicable. If BFTN is Yes, add the tentative deposit date and time.</li>
                    <li>Enter the customer address in Steadfast-friendly format because this address will be sent to courier during parcel creation.</li>
                    <li>Add Remark-1 and Remark-2 for internal notes if needed.</li>
                    <li>Add co-passenger details with Add Co-Passenger when more passengers are included in the booking.</li>
                    <li>Click Review & Submit, verify the modal information, then click Confirm & Save Ticket.</li>
                </ol>
            </div>

            <div id="sales-workflow">
                <h2>6. Sales Status Workflow</h2>
                <p class="text-gray-700 mb-3">
                    Go to Sell > Sales. The status tabs show where each ticket is in the operation flow.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700 border border-gray-200">
                        <thead class="bg-gray-100 text-gray-900">
                            <tr>
                                <th class="px-4 py-3 border">Status</th>
                                <th class="px-4 py-3 border">Meaning</th>
                                <th class="px-4 py-3 border">User Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-3 border font-semibold">Pending</td>
                                <td class="px-4 py-3 border">New booking waiting for payment verification.</td>
                                <td class="px-4 py-3 border">Verify payment or collect due payment if due exists.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 border font-semibold">Payment Verified</td>
                                <td class="px-4 py-3 border">Payment has been checked and accepted.</td>
                                <td class="px-4 py-3 border">Open ticket details, attach/confirm PDF ticket, then issue ticket.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 border font-semibold">Ticket Issued</td>
                                <td class="px-4 py-3 border">Ticket PDF has been issued and is ready for printing.</td>
                                <td class="px-4 py-3 border">Open or print the ticket, then mark it as Ticket Printed.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 border font-semibold">Ticket Printed</td>
                                <td class="px-4 py-3 border">Printed ticket is ready for courier parcel creation.</td>
                                <td class="px-4 py-3 border">Create parcel. This connects with Steadfast and saves the consignment ID.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 border font-semibold">Parcel Created</td>
                                <td class="px-4 py-3 border">Steadfast parcel was created and shipment ID is available.</td>
                                <td class="px-4 py-3 border">After dispatch or delivery confirmation, mark as Shipped.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 border font-semibold">Shipped</td>
                                <td class="px-4 py-3 border">The parcel has been marked as shipped.</td>
                                <td class="px-4 py-3 border">Continue to Collected when the customer receives it.</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 border font-semibold">Collected</td>
                                <td class="px-4 py-3 border">The customer has collected the parcel.</td>
                                <td class="px-4 py-3 border">No further delivery action is required.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="due-payments">
                <h2>7. Due Payment Modal</h2>
                <p class="text-gray-700 mb-3">
                    If a ticket has due amount, the Sales table shows a Pay Due action. Use it when the customer pays
                    full or partial due after ticket creation.
                </p>
                <ol class="list-decimal list-inside text-gray-700 space-y-2">
                    <li>Click Pay Due from the ticket row.</li>
                    <li>Check Total Due Amount in the modal.</li>
                    <li>Enter Paid Amount. The Remaining Due Amount will update automatically.</li>
                    <li>Select payment method: Cash, Bkash, Nagad, or Bank Transfer.</li>
                    <li>Add remark if needed for accounts or customer reference.</li>
                    <li>Click Pay Due and confirm the alert.</li>
                    <li>The system adds a payment record, increases Total Received, and reduces Due Amount.</li>
                </ol>
            </div>

            <div id="shipping">
                <h2>8. Steadfast Parcel and Shipment</h2>
                <p class="text-gray-700 mb-3">
                    Steadfast is used when printed tickets need courier delivery. Parcel creation happens from the
                    Ticket Printed status.
                </p>
                <ol class="list-decimal list-inside text-gray-700 space-y-2">
                    <li>Make sure the ticket is already in Ticket Printed status.</li>
                    <li>Click the parcel creation action from the ticket row.</li>
                    <li>The system sends invoice, customer name, mobile number, address, COD amount, note, and delivery type to Steadfast.</li>
                    <li>Steadfast returns a consignment ID. The system saves it as Shipment ID.</li>
                    <li>The ticket moves to Parcel Created status.</li>
                    <li>After courier dispatch or delivery confirmation, click Shipped to complete the flow.</li>
                </ol>
                <p class="text-gray-700 mt-3">
                    Important: customer address and mobile number must be correct before creating the parcel. Wrong
                    address information can cause courier failure.
                </p>
            </div>

            <div id="searching">
                <h2>9. Ticket Search, Filter, and Opening Details</h2>
                <ul class="list-disc list-inside text-gray-700 space-y-2">
                    <li>Use status tabs to switch between Pending, Payment Verified, Ticket Issued, Ticket Printed, Parcel Created, and Shipped tickets.</li>
                    <li>Use company, ship, and journey date filters to narrow the table.</li>
                    <li>Use the table search box to find tickets by customer name, mobile, status, due amount, shipment ID, or remarks.</li>
                    <li>Open a ticket row to view details, edit information, verify ticket PDFs, and move to the next sale if available.</li>
                    <li>Use Clear Filters when you want to return to the full list.</li>
                </ul>
            </div>

            <div id="refunds">
                <h2>10. Refund Workflow</h2>
                <ol class="list-decimal list-inside text-gray-700 space-y-2">
                    <li>Go to Refund > Make Refund.</li>
                    <li>Find the sale that needs refund and review ticket/payment information.</li>
                    <li>Choose a full or partial refund. For a partial refund, enter only the ticket quantities being returned; do not exceed purchased quantities.</li>
                    <li>Review the calculated refund preview, including other fee, discount, due adjustment, and any extra-payment refund where applicable.</li>
                    <li>Add a clear remark and submit the request.</li>
                    <li>Track the request through Requested, Partner Approved, and Payment Details Added. Record the customer payment to complete the refund.</li>
                    <li>Review completed refund records under Refund > Refunded Sell. Extra-payment refunds are tracked separately.</li>
                </ol>
            </div>

            <div id="reports">
                <h2>11. Reports and Cash Collection</h2>
                <ul class="list-disc list-inside text-gray-700 space-y-2">
                    <li>Open Show Reports > Sales Reports to review sales data for a selected period or filter.</li>
                    <li>Open Show Reports > Cash Collection to check collected amounts and reconcile payments.</li>
                    <li>Use reports for daily closing, company-wise review, and payment method checking.</li>
                    <li>Always match due collection and refund entries before final accounts submission.</li>
                </ul>
            </div>

            <div id="cash-collection">
                <h2>11.1 Available Cash Amount Calculation (Cash Collection)</h2>
                <p class="text-gray-700 mb-3">
                    The <strong>Available Cash Amount</strong> shown in the <em>Add New Cash Collection</em> modal is
                    calculated from recorded sales, completed customer refunds, and previous cash-outs. It is a
                    <strong>read-only</strong> balance used when recording a cash collection or cash-out.
                </p>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-3 font-mono text-sm">
                    Available Cash = Total Received - Customer Refunds Paid - Total Cash-out
                </div>
                <ul class="list-disc list-inside text-gray-700 space-y-2">
                    <li><strong>Total Received:</strong> Sum of <code>received_amount</code> for sales that are not pending.</li>
                    <li><strong>Customer Refunds Paid:</strong> Sum of customer refund amounts for completed refunds on non-pending sales.</li>
                    <li><strong>Total Cash-out:</strong> Sum of <code>cashout_amount</code> of all cash collection entries.</li>
                    <li>A negative result means more money has been refunded / cashed out than was received.</li>
                </ul>
            </div>

            <div id="best-practices">
                <h2>12. Best Practices</h2>
                <ul class="list-disc list-inside text-gray-700 space-y-2">
                    <li>Check duplicate warning before saving a new ticket for the same mobile and journey date.</li>
                    <li>Never create a Steadfast parcel before confirming address, mobile number, and due/COD amount.</li>
                    <li>Use remarks for any unusual payment, refund, customer request, or manual correction.</li>
                    <li>Keep ticket status updated immediately after each operational step.</li>
                    <li>Log out after work, especially on shared office computers.</li>
                </ul>
            </div>

                </main>
            </div>
        </div>
    </div>
</x-app-layout>
