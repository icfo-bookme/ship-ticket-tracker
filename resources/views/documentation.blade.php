{{-- resources/views/documentation.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ticket Tracker Detailed Documentation
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Overview --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3">1. Overview</h3>
                <p class="text-gray-700 mb-2">
                    Ticket Tracker is a comprehensive web-based software designed to help organizations manage ticket
                    sales,
                    customer information, journey schedules, and payments in an organized manner. The system ensures
                    accurate
                    tracking of each ticket, prevents duplicate entries, and simplifies reporting for daily operations.
                </p>
                <p class="text-gray-700">
                    The software is fully responsive, meaning it works seamlessly on desktop, tablet, and mobile
                    devices. It is
                    built to be intuitive and user-friendly, so even non-technical staff can operate it with minimal
                    training.
                    Security features ensure that only authorized users can access sensitive information.
                </p>
            </div>

            {{-- System Requirements --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3">2. System Requirements</h3>
                <p class="text-gray-700 mb-2">
                    To use Ticket Tracker efficiently, users need to meet certain requirements. First, a modern web
                    browser
                    such as Google Chrome, Mozilla Firefox, or Microsoft Edge is required to ensure full compatibility
                    with
                    all features and layouts. The system is web-based, so a stable internet connection is necessary for
                    real-time data updates.
                </p>
                <p class="text-gray-700">
                    Users must also have valid login credentials, which are assigned by the system administrator.
                    Attempting
                    to access the system without proper credentials will result in denial of access to protect sensitive
                    data.
                </p>
            </div>

            {{-- Login & Logout --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3">3. User Authentication</h3>
                <p class="text-gray-700 mb-2">
                    Ticket Tracker employs a secure login system to ensure that only authorized users can access the
                    system.
                    Each user is provided with a unique email or username along with a password. These credentials are
                    required
                    to access the dashboard and other features.
                </p>

                <p class="font-semibold text-gray-800 mb-1">Login Process:</p>
                <p class="text-gray-700 mb-2">
                    To log in, navigate to the Ticket Tracker URL using your browser. Enter your registered email or
                    username
                    and password in the login form, then click the "Login" button. If the credentials are valid, you
                    will be
                    redirected to the dashboard where you can access all permitted features. Invalid login attempts will
                    display an error message.
                </p>

                <p class="font-semibold text-gray-800 mb-1">Logout Process:</p>
                <p class="text-gray-700">
                    After completing your work, it is essential to log out to protect your account and sensitive data.
                    To log out, click on your name in the top navigation bar, then select "Log Out" from the dropdown
                    menu.
                    This action will end your session and redirect you back to the login page.
                </p>
            </div>

            {{-- Navigation Bar --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3">4. Navigation Bar</h3>
                <p class="text-gray-700 mb-2">
                    The navigation bar is located at the top of every page and provides quick access to the main areas
                    of
                    the Ticket Tracker system. On the left, users will see the system logo, which also functions as a
                    shortcut to return to the dashboard at any time.
                </p>
                <p class="text-gray-700 mb-2">
                    On the right side, several interactive elements are available. A documentation button is provided
                    for easy access to this user guide. Next to it, a notification bell icon alerts users about
                    new updates or pending tasks. The user profile dropdown provides links to edit your profile
                    or log out securely. The navigation bar is responsive and adapts to smaller screens using
                    a hamburger menu.
                </p>
            </div>

            {{-- Dashboard --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3">5. Dashboard</h3>
                <p class="text-gray-700 mb-2">
                    The dashboard serves as the central hub of Ticket Tracker. It provides an at-a-glance view of key
                    system metrics including total tickets, today's sales, pending tickets, confirmed tickets, cancelled
                    tickets, and total received payments. These statistics help users quickly assess the status of
                    ticket
                    operations and make informed decisions.
                </p>
                <p class="text-gray-700">
                    The dashboard is updated in real time, ensuring that users always have the most current information
                    available. Visual indicators and tables make it easy to interpret data without requiring advanced
                    technical knowledge.
                </p>
            </div>

            {{-- Ticket Creation --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3">6. Ticket Creation</h3>

                <p class="text-gray-700 mb-2">
                    Ticket creation is the process of recording customer bookings into the Ticket Tracker system. This
                    feature allows users to enter all relevant passenger, journey, ticket, and payment details in a
                    structured
                    form to ensure accurate tracking and reporting.
                </p>

                <p class="text-gray-700 mb-2">
                    To create a new ticket, navigate to the "Create Ticket" section. The form is divided into multiple
                    sections
                    to guide users through the process:
                </p>

                <ul class="list-disc list-inside text-gray-700 mb-2">
                    <li><strong>Passenger Information:</strong> Enter the primary passenger’s full name, mobile number,
                        WhatsApp number (optional), date of birth, NID or passport number, email address, sales source,
                        and
                        associated company.</li>
                    <li><strong>Journey Details:</strong> Provide the ship name, journey date, and optional return date.
                        Accurate journey details ensure proper scheduling and reporting.</li>
                    <li><strong>Ticket Categories:</strong> Select the ticket types for departure and return journeys.
                        The system may display available categories based on the selected ship and journey date.</li>
                    <li><strong>Payment Details:</strong> Add payment information, including payment method, received
                        amount,
                        payment date, and any remarks. The system automatically calculates the total payable, total
                        received,
                        and due amounts.</li>
                    <li><strong>Additional Information:</strong> Include passenger address and optional remarks for
                        internal
                        tracking or customer reference.</li>
                    <li><strong>Co-Passenger Details:</strong> If additional passengers are traveling, you can add
                        co-passenger
                        information here. Each co-passenger entry should include name, NID, and contact information.
                    </li>
                </ul>

                <p class="text-gray-700 mb-2">
                    After filling out all required and optional fields, click the <strong>"Review & Submit"</strong>
                    button.
                    The system will display a summary of all entered data for final verification. Review each section
                    carefully
                    to ensure accuracy.
                </p>

                <p class="text-gray-700 mb-2">
                    Once you confirm that all details are correct, click the <strong>"Submit"</strong> button in the
                    review
                    section. The system will create the ticket sale, generate a unique ticket record, and update all
                    relevant dashboards, including ticket counts, payments, and pending status.
                </p>

                <p class="text-gray-700">
                    Proper use of the ticket creation form ensures that all passenger, journey, and payment information
                    is
                    accurately recorded, preventing errors and simplifying reporting and reconciliation.
                </p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3">7. Ship Ticket Sales</h3>

                <p class="text-gray-700 mb-2">
                    The Ship Ticket Sales page is designed to manage all ticket transactions from booking to shipment.
                    Tickets are categorized by their current status to help staff track, verify, and process them
                    efficiently.
                </p>

                <p class="text-gray-700 mb-2">
                    Ticket statuses are displayed as tabs or filters, allowing users to quickly view tickets based on
                    their progress:
                </p>

                <ul class="list-disc list-inside text-gray-700 mb-2">
                    <li><strong>Pending:</strong> Tickets that have been created but not yet verified or paid. Actions
                        include verifying payment or marking payment as due.</li>
                    <li><strong>Payment Verified:</strong> Tickets with confirmed payments. Actions include issuing
                        tickets for printing.</li>
                    <li><strong>Ticket Issued:</strong> Tickets that have been issued but not yet printed. Actions
                        include printing tickets or opening ticket details.</li>
                    <li><strong>Ticket Printed:</strong> Tickets that have been printed. Actions include adding them to
                        a parcel for shipment.</li>
                    <li><strong>Parcel Created:</strong> Tickets grouped into parcels for shipment. Actions include
                        shipment tracking and confirmation.</li>
                    <li><strong>Shipped:</strong> Tickets that have been delivered or dispatched. This status confirms
                        completion of the ticket delivery process.</li>
                </ul>

                <p class="text-gray-700 mb-2">
                    The table displays the following ticket details for each entry:
                </p>

                <ul class="list-disc list-inside text-gray-700 mb-2">
                    <li><strong>ID:</strong> Unique ticket number generated by the system.</li>
                    <li><strong>Customer Name:</strong> Name of the primary passenger.</li>
                    <li><strong>Mobile:</strong> Contact number of the customer.</li>
                    <li><strong>Ship Name:</strong> Name of the ship for the journey.</li>
                    <li><strong>Shipment ID:</strong> Applicable when tickets are grouped into parcels for shipment.
                    </li>
                    <li><strong>Remark 1 & 2:</strong> Additional notes entered during ticket creation.</li>
                    <li><strong>Action:</strong> Context-specific buttons for processing the ticket, such as <em>Pay
                            Due</em>, <em>Verify Payment</em>, <em>Ticket Issued</em>, <em>Open Ticket</em>, or
                        <em>Shipped</em>.
                    </li>
                </ul>

                <p class="text-gray-700 mb-2">
                    Users can apply filters to streamline ticket management:
                </p>

                <ul class="list-disc list-inside text-gray-700 mb-2">
                    <li><strong>Filter by Source Company:</strong> Select a company to view only tickets associated with
                        that sales source.</li>
                    <li><strong>Filter by Ship:</strong> Choose a ship to see tickets for that particular journey.</li>
                    <li><strong>Filter by Journey Date:</strong> Select a date to display tickets scheduled for a
                        specific day.</li>
                    <li><strong>Clear Filters:</strong> Resets all filters to show all tickets across statuses.</li>
                </ul>

                <p class="text-gray-700 mb-2">
                    The page also includes search functionality and pagination to quickly locate tickets by customer
                    name, mobile number, or remarks.
                    The "Show entries" dropdown allows adjusting the number of tickets displayed per page.
                </p>

                <p class="text-gray-700 mb-2">
                    Workflow Summary:
                </p>

                <ol class="list-decimal list-inside text-gray-700 mb-2">
                    <li>Create a new ticket and fill in all necessary details.</li>
                    <li>Pending tickets are verified for payment.</li>
                    <li>Once payment is verified, tickets are issued and can be printed.</li>
                    <li>Printed tickets are grouped into parcels for shipment if required.</li>
                    <li>Shipped tickets are marked as completed, ensuring full traceability.</li>
                </ol>

                <p class="text-gray-700">
                    By using this page effectively, administrators can manage ticket sales from creation to shipment
                    while maintaining accurate records of payments, passenger details, and journey statuses.
                </p>
            </div>



            {{-- Ticket List and Management --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3">7. Ticket List and Management</h3>
                <p class="text-gray-700 mb-2">
                    The ticket list provides a comprehensive view of all tickets stored in the system. Users can search,
                    filter, and sort tickets to locate specific records efficiently. Each ticket displays customer
                    details,
                    journey information, payment status, and ticket status.
                </p>
                <p class="text-gray-700">
                    Filtering options allow users to view tickets based on their status, such as pending, confirmed, or
                    cancelled. Pagination and search functionality help manage large volumes of tickets effectively.
                </p>
            </div>

            {{-- Payment Management --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3">8. Payment Management</h3>
                <p class="text-gray-700 mb-2">
                    Ticket Tracker includes automated payment management. When creating or editing tickets, the system
                    calculates the due amount by subtracting the received amount from the total ticket fee. This ensures
                    accurate tracking of payments and simplifies accounting.
                </p>
                <p class="text-gray-700">
                    Users can view payment details directly in the ticket list, helping them monitor pending and
                    completed
                    payments. Reports can be generated based on payment status for accounting and reconciliation
                    purposes.
                </p>
            </div>

            {{-- Ticket Status --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3">9. Ticket Status</h3>
                <p class="text-gray-700 mb-2">
                    Each ticket in the system has a defined status that reflects its current stage in the booking
                    process.
                    The statuses include Pending, Confirmed, and Cancelled. These statuses help staff track tickets and
                    respond appropriately to customer requests.
                </p>
                <p class="text-gray-700">
                    Status changes can occur automatically based on payments or manually by authorized users. Proper use
                    of statuses ensures accurate reporting and prevents errors in ticket management.
                </p>
            </div>

            {{-- Additional Notes --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-3">10. Additional Notes</h3>
                <p class="text-gray-700 mb-2">
                    For best practices, users should always log out after finishing their work, double-check customer
                    information before submitting tickets, and avoid creating duplicate entries. The system is designed
                    to prevent common errors, but careful data entry ensures maximum efficiency.
                </p>
                <p class="text-gray-700">
                    The documentation button and notifications on the navbar provide quick access to guides and alerts.
                    Regular review of dashboard metrics and ticket statuses helps maintain smooth operations and
                    improves
                    overall productivity.
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
