<x-app-layout>
    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white">Add New Ship Ticket Sale</h1>

        <!-- Success/Error Messages -->
        @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
            {{ session('success') }}
        </div>
        @endif

        @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd" />
                </svg>
                <ul class="list-disc ml-5 space-y-1">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <!-- Form Card -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <form id="ticketForm" action="{{ route('ship-ticket-sales.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Customer & Contact Info -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Passenger Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}"
                            placeholder="Full name"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Mobile <span class="text-red-500">*</span> <span class="text-xs text-gray-500">[11 digits, no +88 or spaces]</span>
                        </label>
                        <input type="number" name="customer_mobile" value="{{ old('customer_mobile') }}"
                            placeholder="01XXXXXXXXX"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Date Of Birth <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            NID <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nid" value="{{ old('nid') }}" placeholder="9203-746-48734"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="abc@gmail.com"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Sales Source
                        </label>
                        <select name="sales_source"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <option value="">Select source</option>
                            <option value="WhatsApp(019)" {{ old('sales_source') == 'WhatsApp(019)' ? 'selected' : '' }}>
                                WhatsApp(019)</option>
                            <option value="WhatsApp(018)" {{ old('sales_source') == 'WhatsApp(018)' ? 'selected' : '' }}>
                                WhatsApp(018)</option>
                            <option value="WhatsApp(016)" {{ old('sales_source') == 'WhatsApp(016)' ? 'selected' : '' }}>
                                WhatsApp(016)</option>
                            <option value="Facebook" {{ old('sales_source') == 'Facebook' ? 'selected' : '' }}>Facebook
                            </option>
                            <option value="Messenger" {{ old('sales_source') == 'Messenger' ? 'selected' : '' }}>
                                Messenger</option>
                            <option value="Walk-in" {{ old('sales_source') == 'Walk-in' ? 'selected' : '' }}>Walk-in
                            </option>
                            <option value="Others" {{ old('sales_source') == 'Others' ? 'selected' : '' }}>Others
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Ship & Journey Info -->
                <div class="grid grid-cols-3 gap-4">
                    

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Journey Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="journey_date" id="journey_date" value="{{ old('journey_date') }}"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Return Date
                        </label>
                        <input type="date" name="return_date" id="return_date" value="{{ old('return_date') }}"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ship Name <span class="text-red-500">*</span>
                        </label>
                        <select name="ship_id" id="ship_id"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                            <option value="">Select a Ship</option>
                            @foreach ($ships as $ship)
                            <option value="{{ $ship->id }}" {{ old('ship_id') == $ship->id ? 'selected' : '' }}>
                                {{ $ship->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Ticket Categories -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="mt-6 border border-blue-200 dark:border-blue-800 rounded-lg p-5 bg-blue-50 dark:bg-blue-900/20">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4"> Departure Journey Tickets Category</h3>
                        <div id="departureTicketCategoriesContainer" class="space-y-4">
                            <!-- Dynamic departure ticket category fields will appear here -->
                        </div>
                        <div id="noDepartureCategoriesMessage" class="text-gray-500 dark:text-gray-400 text-sm mt-2">
                            Select a ship to see available ticket categories.
                        </div>
                    </div>

                    <!-- Return Journey Ticket Categories -->
                    <div class="mt-6 border border-green-200 dark:border-green-800 rounded-lg p-5 bg-green-50 dark:bg-green-900/20" id="returnJourneySection" style="display: none;">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Return Journey Tickets Category</h3>
                        <div id="returnTicketCategoriesContainer" class="space-y-4">
                            <!-- Dynamic return ticket category fields will appear here -->
                        </div>
                        <div id="noReturnCategoriesMessage" class="text-gray-500 dark:text-gray-400 text-sm mt-2">
                            Select a ship and return date to see available ticket categories.
                        </div>
                    </div>
                </div>

                <!-- Ticket Summary -->
                <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Ticket Summary</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Total Number of Tickets
                            </label>
                            <input type="number" id="total_tickets" name="total_tickets" value="0" min="0" readonly
                                class="w-full border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Total Ticket Price (৳) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="ticket_fee" name="ticket_fee" value="{{ old('ticket_fee', 0) }}"
                                step="0.01" min="0" placeholder="0.00"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Payment Method <span class="text-red-500">*</span>
                            </label>
                            <select name="payment_method" id="payment_method"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                required>
                                <option value="">Select method</option>
                                <option value="Cash" {{ old('payment_method') == 'Cash' ? 'selected' : '' }}>Cash
                                </option>
                                <option value="Bkash" {{ old('payment_method') == 'Bkash' ? 'selected' : '' }}>Bkash
                                    (+2%)
                                </option>
                                <option value="Nagad" {{ old('payment_method') == 'Nagad' ? 'selected' : '' }}>Nagad
                                    (+2%)
                                </option>
                                <option value="Bank Transfer"
                                    {{ old('payment_method') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="grid grid-cols-3 gap-4 mt-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Company <span class="text-red-500">*</span>
                        </label>
                        <select name="company_id"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                            <option value="">Select a Source</option>
                            @foreach ($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Total Payable (৳)
                        </label>
                        <input type="number" id="total_payable" name="total_payable"
                            value="{{ old('total_payable', 0) }}" step="0.01" min="0" placeholder="0.00" readonly
                            class="w-full border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Received (৳) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="received_amount" name="received_amount"
                            value="{{ old('received_amount', 0) }}" step="0.01" min="0" placeholder="0.00"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Due Amount (৳)
                        </label>
                        <input type="number" id="due_amount" name="due_amount" value="{{ old('due_amount', 0) }}"
                            step="0.01" min="0" placeholder="0.00" readonly
                            class="w-full border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2">
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Full Address
                        <span class="text-xs text-gray-500">(Format: Fla# A1, House# 17/1, Road# 3/A, Dhanmondi, Dhaka-1209)</span>
                    </label>
                    <textarea id="address" name="address" placeholder="Enter your address here" rows="3"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">{{ old('address') }}</textarea>
                </div>

                <!-- Additional Info -->
                <div class="hidden">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Issued Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="issued_date" value="{{ old('issued_date', date('Y-m-d')) }}"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Sold By <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="sold_by" value="{{ old('sold_by', Auth::user()->name ?? '') }}"
                            placeholder="Seller name" readonly
                            class="w-full border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2">
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-lg font-semibold text-gray-800 dark:text-white mb-3">
                        Co-Passenger Details
                    </label>
                    <div id="coPassengersWrapper" class="space-y-4">
                        <!-- Add button -->
                        <button type="button" id="addCoPassengerBtn"
                            class="mt-3 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:bg-blue-700 dark:hover:bg-blue-800 transition">
                            + Add Co-Passenger
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('ship-ticket-sales.create') }}"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition">
                        Cancel
                    </a>
                    <button type="button" id="reviewButton"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition">
                        Review & Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" tabindex="-1" aria-hidden="true"
        class="hidden fixed inset-0 z-50 flex items-center justify-center overflow-y-auto">

        <!-- Backdrop -->
        <div id="modalBackdrop" class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <!-- Modal Container -->
        <div class="relative w-full max-w-2xl mx-auto my-8 p-4">
            <!-- Modal Content -->
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-lg flex flex-col max-h-[90vh]">

                <!-- Header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Review Ticket Information
                    </h3>
                    <button type="button"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg p-1.5 transition"
                        data-modal-hide="reviewModal">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M1 1l6 6m0 0l6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                    </button>
                </div>

                <!-- Body (Scrollable) -->
                <div class="p-4 md:p-5 overflow-y-auto flex-1">
                    <div id="reviewContent" class="space-y-4">
                        <!-- Populated dynamically by JS -->
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end p-4 md:p-5 border-t dark:border-gray-700 gap-3">
                    <button type="button" id="editInfoButton"
                        class="px-5 py-2.5 text-sm font-medium text-gray-800 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 transition">
                        Edit Information
                    </button>

                    <button type="submit" form="ticketForm"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 rounded-lg dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800 transition">
                        Confirm & Save Ticket
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include custom JavaScript -->
    <script src="{{ asset('js/ship-ticket-sales.js') }}"></script>
</x-app-layout>