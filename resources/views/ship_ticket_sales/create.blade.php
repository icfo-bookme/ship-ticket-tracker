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
                            Customer Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}"
                            placeholder="Full name"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Mobile <span class="text-red-500">*</span> <span> [11 digits, no +88 or spaces] </span>
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
                            NID <span class="text-red-500">*</span> <span> </span>
                        </label>
                        <input type="nid" name="nid" value="{{ old('nid') }}" placeholder="9203-746-48734"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Email <span class="text-red-500">*</span> <span> </span>
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
                            <option value="WhatsApp(019)" {{ old('sales_source') == 'WhatsApp(019)' ? 'selected' : '' }}
                                selected>
                                WhatsApp(019)</option>
                            <option value="WhatsApp(018)" {{ old('sales_source') == 'WhatsApp(018)' ? 'selected' : '' }}
                                selected>
                                WhatsApp(018)</option>
                            <option value="WhatsApp(016)"
                                {{ old('sales_source') == 'WhatsApp(016)' ? 'selected' : '' }} selected>
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
                            Ship Name <span class="text-red-500">*</span>
                        </label>
                        <select name="ship_id"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                            <option value="">Select a Ship</option>
                            @foreach ($ships as $ship)
                                <option value="{{ $ship->id }}"
                                    {{ old('ship_id') == $ship->id ? 'selected' : '' }}>
                                    {{ $ship->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Journey Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="journey_date" value="{{ old('journey_date') }}"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Return Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="return_date" value="{{ old('return_date') }}"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>


                </div>

                <!-- Payment Info -->
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Company <span class="text-red-500">*</span>
                        </label>
                        <select name="company_id"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                            <option value="">Select a Source</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}"
                                    {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Number Of Ticket <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="number_of_ticket" name="number_of_ticket"
                            value="{{ old('ticket_fee') }}" step="1" min="1"
                            placeholder="Enter Number Of Ticket"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Total Ticket Price (৳) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="ticket_fee" name="ticket_fee" value="{{ old('ticket_fee') }}"
                            step="0.01" min="0" placeholder="0.00"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Payment Method <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_method"
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

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Total Payable (৳)
                        </label>
                        <input type="number" id="total_payable" name="total_payable"
                            value="{{ old('total_payable', 0) }}" step="0.01" min="0" placeholder="0.00"
                            class="w-full border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-600 dark:text-white rounded-lg px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Received (৳) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="received_amount" name="received_amount"
                            value="{{ old('received_amount') }}" step="0.01" min="0" placeholder="0.00"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Due Amount (৳)
                        </label>
                        <input type="number" id="due_amount" name="due_amount" value="{{ old('due_amount', 0) }}"
                            step="0.01" min="0" placeholder="0.00" readonly
                            class="w-full border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-600 dark:text-white rounded-lg px-3 py-2">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Ticket Category <span class="text-red-500">*</span>
                        </label>
                        <select name="ticket_category" id="ticket_category"
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                            required>
                            <option value="">Select a Package</option>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                    </div>
                </div>

                <div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Full Address (please follow this format:
                            <span class="font-semibold text-gray-900 dark:text-white">Fla# A1, House# 17/1, Road# 3/A,
                                Dhanmondi, Dhaka-1209</span>)
                        </label>
                        <textarea id="address" name="address" placeholder="Enter your address here"
                            class="w-full border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-600 dark:text-white rounded-lg ">
            {{ old('address') }}
        </textarea>
                    </div>
                </div>



                <!-- Additional Info -->
                <div class=" gap-4 hidden">
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
                            class="w-full border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-600 dark:text-white rounded-lg px-3 py-2">
                    </div>
                </div>




                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('ship-ticket-sales.create') }}"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition">
                        Cancel
                    </a>
                    <!-- Remove data-modal-target and data-modal-toggle from this button -->
                    <button type="button" id="reviewButton"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 transition">
                        Review & Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" tabindex="-1" aria-hidden="true"
        class="hidden overflow-hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0  max-h-xl">
        <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity" id="modalBackdrop"></div>
        <div class="relative   p-4 w-full max-w-2xl max-h-full">
            <!-- Modal content -->
            <div class="relative  bg-white rounded-lg shadow dark:bg-gray-800">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Review Ticket Information
                    </h3>
                    <button type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        data-modal-hide="reviewModal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>

                <!-- Modal body -->
                <div class="p-4 md:p-5 space-y-4">
                    <div id="reviewContent" class="space-y-4">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>

                <!-- Modal footer -->
                <div
                    class="flex items-center justify-end p-4 md:p-5 border-t border-gray-200 rounded-b dark:border-gray-600 gap-3">
                    <button type="button" id="editInfoButton"
                        class="px-5 py-2.5 text-sm font-medium text-gray-900 ...">
                        Edit Information
                    </button>

                    <button type="submit" form="ticketForm"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 rounded-lg dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                        Confirm & Save Ticket
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include custom JavaScript -->
    <script src="{{ asset('js/ship-ticket-sales.js') }}"></script>
</x-app-layout>
