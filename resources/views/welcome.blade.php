<x-frontend-layout>

    <div class="max-w-7xl mx-auto pt-12 lg:p-6">
        <div class="bg-white rounded-lg mb-3 overflow-hidden border-t-8 border-[#673ab7] ">
            <div class="px-8 py-3">
                <h1 class="text-3xl font-normal text-gray-800 mb-2">Ship Ticket Form</h1>
                <p class="text-sm text-gray-600">Please fill in the passenger details and ticket information below</p>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if (session('success'))
            <div
                class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4  rounded-lg mb-6 shadow-sm">
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
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
        <div class=" dark:bg-gray-800  rounded-xl overflow-hidden">
            <form id="ticketForm" action="{{ route('publicForm.store') }}" method="POST" class="">
                @csrf

                <!-- Customer & Contact Info -->
                <div
                    class=" mb-3 bg-blue-50 p-8 rounded-xl border border-green-200 dark:bg-green-900/20 dark:border-green-800">
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-user text-blue-950"></i>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Passenger Information</h2>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Basic details of the primary passenger
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Passenger Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="customer_name" value="{{ old('customer_name') }}"
                                placeholder="Full name"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm"
                                required>
                        </div>

                        <!-- Mobile Number -->
                        <div>
                            <label for="customer_mobile"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Mobile Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="customer_mobile" name="customer_mobile"
                                placeholder="Enter mobile number (01XXXXXXXXX)"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 transition shadow-sm">
                        </div>

                        <!-- WhatsApp Number -->
                        <div>
                            <label for="whatsapp"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                WhatsApp Number <span class="text-red-500">*</span>
                            </label>

                            <div class="space-y-2">
                                <!-- WhatsApp Input -->
                                <input type="text" id="whatsapp" name="whatsapp" placeholder="Enter WhatsApp number"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 transition shadow-sm">

                                <!-- Checkbox -->
                                <label for="sameAsMobileCheckbox"
                                    class="flex items-center gap-2 cursor-pointer select-none">
                                    <input type="checkbox" id="sameAsMobileCheckbox"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded 
                           dark:bg-gray-700 dark:border-gray-600 focus:ring-2 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Same as Mobile</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Date Of Birth <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                NID <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nid" value="{{ old('nid') }}"
                                placeholder="9203-746-48734"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email (optional)
                            </label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="abc@gmail.com"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">
                        </div>

                        <div class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Sales Source
                            </label>
                            <select name="sales_source"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">
                                <option value="">Select source</option>
                                <option value="WhatsApp(019)"
                                    {{ old('sales_source') == 'WhatsApp(019)' ? 'selected' : '' }}>
                                    WhatsApp(019)</option>
                                <option value="WhatsApp(018)"
                                    {{ old('sales_source') == 'WhatsApp(018)' ? 'selected' : '' }}>
                                    WhatsApp(018)</option>
                                <option value="WhatsApp(016)"
                                    {{ old('sales_source') == 'WhatsApp(016)' ? 'selected' : '' }}>
                                    WhatsApp(016)</option>
                                <option value="Facebook" {{ old('sales_source') == 'Facebook' ? 'selected' : '' }}>
                                    Facebook
                                </option>
                                <option value="Messenger" {{ old('sales_source') == 'Messenger' ? 'selected' : '' }}>
                                    Messenger</option>
                                <option value="Walk-in" {{ old('sales_source') == 'Walk-in' ? 'selected' : '' }}>
                                    Walk-in
                                </option>
                                <option value="Others" {{ old('sales_source') == 'Others' ? 'selected' : '' }}>Others
                                </option>
                            </select>
                        </div>
                        <div class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Company <span class="text-red-500">*</span>
                            </label>
                            <select name="company_id"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">
                                <option value="">Select a Source</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Ship & Journey Info -->
                <div
                    class="mb-3 bg-blue-50 p-8 rounded-xl border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800">
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-route text-blue-950"></i>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Journey Details</h2>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Information about the ship and journey
                            dates</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Journey Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="journey_date" id="journey_date"
                                value="{{ old('journey_date') }}"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Return Date
                            </label>
                            <input type="date" name="return_date" id="return_date"
                                value="{{ old('return_date') }}"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Ship Name <span class="text-red-500">*</span>
                            </label>
                            <select name="ship_id" id="ship_id"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">
                                <option value="">Select a Ship</option>
                                @foreach ($ships as $ship)
                                    <option value="{{ $ship->id }}"
                                        {{ old('ship_id') == $ship->id ? 'selected' : '' }}>
                                        {{ $ship->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Ticket Categories -->
                <div
                    class="mb-3 bg-blue-50 p-8 rounded-xl border border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800">
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-ticket text-blue-950"></i>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Ticket Categories</h2>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Select ticket types for departure and
                            return journeys</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div
                            class="border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6 bg-blue-50 dark:bg-blue-900/20 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                Departure Journey Tickets Category
                            </h3>
                            <div id="departureTicketCategoriesContainer" class="space-y-4">
                                <!-- Dynamic departure ticket category fields will appear here -->
                            </div>
                            <div id="noDepartureCategoriesMessage"
                                class="text-gray-500 dark:text-gray-400 text-sm mt-2">
                                Select a ship to see available ticket categories.
                            </div>
                        </div>

                        <!-- Return Journey Ticket Categories -->
                        <div class="border-2 border-green-200 dark:border-green-800 rounded-xl p-6 bg-green-50 dark:bg-green-900/20 shadow-sm"
                            id="returnJourneySection" style="display: none;">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                Return Journey Tickets Category
                            </h3>
                            <div id="returnTicketCategoriesContainer" class="space-y-4">
                                <!-- Dynamic return ticket category fields will appear here -->
                            </div>
                            <div id="noReturnCategoriesMessage" class="text-gray-500 dark:text-gray-400 text-sm mt-2">
                                Select a ship and return date to see available ticket categories.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div
                    class="mb-3  bg-blue-50 p-8 rounded-xl border border-gray-200 dark:bg-gray-900/20 dark:border-gray-800">
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-credit-card text-blue-950"></i>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Payment Details</h2>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Add payment methods and amounts</p>
                    </div>

                    <div id="paymentInfoWrapper" class="space-y-4">
                        <!-- Add button -->
                        <button type="button" id="addPaymentInfo"
                            class="mt-3 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:bg-blue-700 dark:hover:bg-blue-800 transition flex items-center shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Payment
                        </button>
                    </div>
                </div>

                <!-- Ticket Summary -->
                <div
                    class="space-y-6 mb-3 bg-blue-50 p-8 rounded-xl border border-slate-200 dark:bg-slate-900/20 dark:border-slate-800">
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">

                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-ticket text-blue-950"></i>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Ticket Summary</h2>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Overview of ticket quantities and
                            pricing</p>
                    </div>

                    <div
                        class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 shadow-sm">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Total Number of Tickets
                                </label>
                                <input type="number" id="total_tickets" name="number_of_ticket" value="0"
                                    min="0" readonly
                                    class="w-full border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Total Ticket Price (৳) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="ticket_fee" name="ticket_fee"
                                    value="{{ old('ticket_fee', 0) }}" step="0.01" min="0"
                                    placeholder="0.00" readonly
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Other Fee(Vat,Tax,etc if include ) (৳) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="other_fee" name="other_fee"
                                    value="{{ old('ticket_fee', 0) }}" step="0.01" min="0"
                                    placeholder="0.00"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div
                    class="space-y-6 mb-3 bg-blue-50 p-8 rounded-xl border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800">
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-credit-card text-blue-950"></i>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Payment Summary</h2>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Financial overview of the transaction
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Total Payable (৳)
                            </label>
                            <input type="number" id="total_payable" name="total_payable"
                                value="{{ old('total_payable', 0) }}" step="0.01" min="0"
                                placeholder="0.00" readonly
                                class="w-full border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 shadow-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Total Paid (৳) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="received_amount" name="received_amount"
                                value="{{ old('received_amount', 0) }}" step="0.01" min="0" readonly
                                placeholder="0.00"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm"
                                required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Due Amount (৳)
                            </label>
                            <input type="number" id="due_amount" name="due_amount"
                                value="{{ old('due_amount', 0) }}" step="0.01" min="0" placeholder="0.00"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 shadow-sm">
                        </div>
                    </div>
                </div>

                <!-- Address & Remarks -->
                <div
                    class="space-y-6 mb-3 bg-blue-50 p-8 rounded-xl border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800">
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-address-card text-blue-950"></i>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Additional Information</h2>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Address and remarks for the booking
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Full Address
                            <span class="text-xs text-gray-500">(Format: Fla# A1, House# 17/1, Road# 3/A, Dhanmondi,
                                Dhaka-1209)</span>
                        </label>
                        <textarea id="address" name="address" placeholder="Enter your address here" rows="3"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">{{ old('address') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Remark-1
                            </label>
                            <textarea id="remark1" name="remark1" placeholder="Enter Remark here" rows="3"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">{{ old('address') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Remark-2
                            </label>
                            <textarea id="remark2" name="remark2" placeholder="Enter your Remark here" rows="3"
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition shadow-sm">{{ old('address') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Co-Passengers -->
                <div
                    class="space-y-6 mb-3 bg-blue-50 p-8 rounded-xl border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800">
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-user-group text-blue-950"></i>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Co-Passenger Details</h2>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Add information for additional
                            passengers</p>
                    </div>

                    <div id="coPassengersWrapper" class="space-y-4">
                        <!-- Add button -->
                        <button type="button" id="addCoPassengerBtn"
                            class="mt-3 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:bg-blue-700 dark:hover:bg-blue-800 transition flex items-center shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Co-Passenger
                        </button>
                    </div>
                </div>

                <!-- Hidden Fields -->
                <div class="hidden">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Issued Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="issued_date" value="{{ old('issued_date', date('Y-m-d')) }}"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>
                    <input type="number" name="sales_source" value="{{ $form }}"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            >
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Sold By <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="sold_by" value="{{ old('sold_by', Auth::user()->name ?? '') }}"
                            placeholder="Seller name" readonly
                            class="w-full border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-white rounded-lg px-4 py-3">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-4 pt-8 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('ship-ticket-sales.create') }}"
                        class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition shadow-sm">
                        Cancel
                    </a>
                    <button type="button" id="reviewButton"
                        class="px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:bg-blue-700 dark:hover:bg-blue-800 dark:focus:ring-blue-800 transition shadow-sm flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>



    <!-- Include custom JavaScript -->
    <script src="{{ asset('js/public-form.js') }}"></script>


</x-frontend-layout>
