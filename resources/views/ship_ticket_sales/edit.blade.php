Warning: truncated output (original token count: 30626)
Total output lines: 1768

<x-app-layout>

    <div class="flex justify-between items-center mt-1 ml-5">
        <h2 class="font-semibold text-base text-gray-800 leading-tight">
            <i class="fas fa-edit mr-2 text-blue-600"></i>
            Ship Ticket Sale #{{ $sale->id }}
        </h2>
        <a href="/sales/status/{{ $sale->status }}"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-2.5 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5">
            <i class="fas fa-arrow-left mr-2"></i> Back to List
        </a>

    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-2">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-2">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="py-8">
        <div class=" mx-auto sm:px-6 ">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="">
                    @if ($errors->any())
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-3 shadow-sm">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-500 text-base"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-red-800 font-semibold">Whoops! There were some problems with your
                                        input.</h3>
                                    <ul class="mt-1 text-red-700 list-disc list-inside text-sm">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                    <form action="{{ route('ship-ticket-sales.update', $sale->id) }}" method="POST" class=""
                        id="ticketForm" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="{{ old('status', $sale->status) }}">

                        <!-- Customer Information -->
                        <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100">
                            <div class="flex items-center justify-between  ">
                                <div class="flex items-center mb-2">
                                    <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                        <i class="fas fa-user text-white text-sm"></i>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-800">Customer Information</h3>
                                </div>

                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <!-- Customer ID is hidden on this page; the value is still submitted with the form. -->
                                <input type="hidden" name="id" id="id" value="{{ old('id', $sale->id) }}">
                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="customer_name"
                                            class="block text-sm font-semibold text-gray-700">Customer Name *</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="customer_name" title="Copy Customer Name">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="text" name="customer_name" id="customer_name" required
                                        value="{{ old('customer_name', $sale->customer_name) }}"
                                        class="copyable-field bg-red-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                </div>

                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="customer_mobile"
                                            class="block text-sm font-semibold text-gray-700">Mobile Number *</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="customer_mobile" title="Copy Mobile Number">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="text" name="customer_mobile" id="customer_mobile" required
                                        value="{{ old('customer_mobile', $sale->customer_mobile) }}"
                                        class="copyable-field w-full bg-red-500 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="whatsapp"
                                            class="block text-sm font-semibold text-gray-700">WhatsApp</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="whatsapp" title="Copy WhatsApp">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="text" name="whatsapp" id="whatsapp"
                                        value="{{ old('whatsapp', $sale->whatsapp) }}"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                </div>

                                <div>
                                    <label for="whatsapp_username" class="block text-sm font-semibold text-gray-700">WhatsApp Username</label>
                                    <input type="text" name="whatsapp_username" id="whatsapp_username" maxlength="100"
                                        value="{{ old('whatsapp_username', $sale->whatsapp_username) }}"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm py-1.5 px-2.5">
                                    @error('whatsapp') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                                    @error('whatsapp_username') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="email"
                                            class="block text-sm font-semibold text-gray-700">Email</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="email" title="Copy Email">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="email" name="email" id="email"
                                        value="{{ old('email', $sale->email) }}"
                                        class="copyable-field bg-red-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                </div>


                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="nid"
                                            class="block text-sm font-semibold text-gray-700">NID</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="nid" title="Copy NID">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="text" name="nid" id="nid"
                                        value="{{ old('nid', $sale->nid) }}"
                                        class="copyable-field bg-red-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                </div>

                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="date_of_birth"
                                            class="block text-sm font-semibold text-gray-700">Date of Birth</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="date_of_birth" title="Copy Date of Birth">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="date" name="date_of_birth" id="date_of_birth" max="{{ now()->subYears(18)->format('Y-m-d') }}"
                                        value="{{ old('date_of_birth', $sale->date_of_birth) }}"
                                        class="copyable-field bg-red-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                </div>

                                <div class="md:col-span-3 flex items-center gap-2">
                                    <input type="hidden" name="collect_from_office" value="0">
                                    <input type="checkbox" id="collect_from_office" name="collect_from_office" value="1"
                                        @checked(old('collect_from_office', $sale->collect_from_office))
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <label for="collect_from_office" class="text-sm font-semibold text-gray-700">
                                        Collect from office
                                    </label>
                                </div>

                                <div class="md:col-span-3" id="addressFieldWrapper">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="address"
                                            class="block text-sm font-semibold text-gray-700">Address</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="address" title="Copy Address">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <textarea name="address" id="address" rows="3"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">{{ old('address', $sale->address) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Ticket Information -->
                        <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-2">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-ticket-alt text-white text-sm"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-800">Ticket Information</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="ship_id" class="block text-sm font-semibold text-gray-700">Ship
                                            *</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="ship_id" title="Copy Ship">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <select name="ship_id" id="ship_id" required
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                        <option value="">Select Ship</option>
                                        @foreach ($ships as $ship)
                                            <option value="{{ $ship->id }}"
                                                {{ $sale->ship_id == $ship->id ? 'selected' : '' }}>
                                                {{ $ship->name }} - {{ $ship->route }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="company_id"
                                            class="block text-sm font-semibold text-gray-700">Company *</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="company_id" title="Copy Company">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <select name="company_id" id="company_id" required
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                        <option value="">Select Company</option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}"
                                                {{ $sale->company_id == $company->id ? 'selected' : '' }}>
                                                {{ $company->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="journey_date"
                                        class="block text-sm font-semibold text-gray-700">Departure Date *</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="journey_date" title="Copy Departure Date">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="date" name="journey_date" id="journey_date"
                                        value="{{ old('journey_date', $sale->journey_date) }}"
                                        class="copyable-field bg-red-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                </div>

                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="return_date"
                                            class="block text-sm font-semibold text-gray-700">Return Date</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="return_date" title="Copy Return Date">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="date" name="return_date" id="return_date"
                                        value="{{ old('return_date', $sale->return_date) }}"
                                        class="copyable-field bg-red-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                </div>

                                <div class="bg-green-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="number_of_ticket"
                                            class="block text-sm font-semibold text-gray-700">Number of Tickets
                                            *</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="number_of_ticket" title="Copy Number of Tickets">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="number" name="number_of_ticket" id="number_of_ticket" required readonly
                                        min="1" value="{{ old('number_of_ticket', $sale->number_of_ticket) }}"
                                        class="copyable-field bg-green-50 w-full border-gray-300 rounded-lg shadow-sm py-1.5 px-2.5">
                                </div>


                            </div>
                        </div>

                        <!-- Package Selection -->
                        <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-3">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-boxes text-white text-sm"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-800">Package Selection</h3>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Departure Packages -->
                                <div class="bg-white rounded-lg p-3 shadow-sm border border-blue-200">
                                    <h4 class="font-bold text-sm text-blue-800 mb-2 flex items-center">
                                        <i class="fas fa-ship mr-2 text-blue-600"></i>
                                        Departure Packages
                                    </h4>
                                    <div class="space-y-3" id="departure-packages-container">
                                        @foreach ($sale->ships->packages as $package)
                                            @php
                                                $departureCategory = $sale->categories
                                                    ->where('type', 'departure')
                                                    ->where('package_id', $package->id)
                                                    ->first();
                                                $departureQuantity = $departureCategory
                                                    ? $departureCategory->quantity
                                                    : 0;
                                            @endphp
                                            <div
                                                class="grid grid-cols-2 items-center p-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out">
                                                <div class="flex items-center">
                                                    <div class="block text-sm font-medium text-gray-700">
                                                        <span class="font-semibold">{{ $package->name }}</span>
                                                        <span
                                                            class="text-blue-600 font-bold ml-2">৳{{ number_format($package->price, 2) }}</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-end space-x-2">
                                                    <label for="departure_quantity_{{ $package->id }}"
                                                        class="text-sm font-semibold text-gray-700">
                                                        Quantity:
                                                    </label>
                                                    <input type="number"
                                                        name="departure_quantity[{{ $package->id }}]"
                                                        id="departure_quantity_{{ $package->id }}"
                                                        value="{{ $departureQuantity }}" min="0"
                                                        data-package-id="{{ $package->id }}"
                                                        data-package-price="{{ $package->price }}"
                                                        data-round-trip-price="{{ $package->round_trip_price }}"
                                                        class="ticket-category-quantity w-20 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5 text-center">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Return Packages -->
                                <div class="bg-white rounded-lg p-3 shadow-sm border border-blue-200">
                                    <h4 class="font-bold text-sm text-blue-800 mb-2 flex items-center">
                                        <i class="fas fa-undo-alt mr-2 text-blue-600"></i>
                                        Return Packages
                                    </h4>
                                    <div class="space-y-3" id="return-packages-container">
                                        @foreach ($sale->ships->packages as $package)
                                            @php
                                                $returnCategory = $sale->categories
                                                    ->where('type', 'return')
                                                    ->where('package_id', $package->id)
                                                    ->first();
                                                $returnQuantity = $returnCategory ? $returnCategory->quantity : 0;
                                            @endphp
                                            <div
                                                class="grid grid-cols-2 items-center p-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out">
                                                <div class="flex items-center">
                                                    <div class="block text-sm font-medium text-gray-700">
                                                        <span class="font-semibold">{{ $package->name }}</span>
                                                        <span class="text-blue-600 font-bold ml-2">
                                                            ৳{{ number_format($package->round_trip_price - $package->price, 2) }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-end space-x-2">
                                                    <label for="return_quantity_{{ $package->id }}"
                                                        class="text-sm font-semibold text-gray-700">
                                                        Quantity:
                                                    </label>
                                                    <input type="number" name="return_quantity[{{ $package->id }}]"
                                                        id="return_quantity_{{ $package->id }}"
                                                        value="{{ $returnQuantity }}" min="0"
                                                        data-package-id="{{ $package->id }}"
                                                        data-package-price="{{ $package->price }}"
                                                        data-round-trip-price="{{ $package->round_trip_price }}"
                                                        class="ticket-category-quantity w-20 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5 text-center">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Information -->
                        <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-3">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-money-bill-wave text-white text-sm"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-800">Financial Summary</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                                <!-- Ticket Fee -->
                                <div class="bg-white rounded-lg p-3 shadow-sm border border-blue-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="ticket_fee"
                                            class="block text-sm font-semibold text-gray-700">Total Ticket Fee
                                            *</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="ticket_fee" title="Copy Ticket Fee">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-gray-500 mr-2">৳</span>
                                        <input type="number" step="0.01" min="0" name="ticket_fee" id="ticket_fee" readonly
                                            required value="{{ old('ticket_fee', $sale->ticket_fee) }}"
                                            class="copyable-field w-full border-gray-300 bg-gray-50 rounded-lg shadow-sm py-1.5 px-2.5 text-sm font-bold text-gray-800">
                                    </div>
                                </div>

                                <!-- Other Fee -->
                                <div class="bg-white rounded-lg p-3 shadow-sm border border-blue-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="other_fee" class="block text-sm font-semibold text-gray-700">Other
                                            Fee</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="other_fee" title="Copy Other Fee">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-gray-500 mr-2">৳</span>
                                        <input type="number" step="0.01" name="other_fee" id="other_fee"
                                            value="{{ old('other_fee', $sale->other_fee) }}"
                                            class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5 text-sm font-bold text-gray-800">
                                    </div>
                                </div>

                                <!-- Discount -->
                                <div class="bg-white rounded-lg p-3 shadow-sm border border-amber-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="discount_amount"
                                            class="block text-sm font-semibold text-gray-700">Discount
                                            Amount</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="discount_amount" title="Copy Discount Amount">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-gray-500 mr-2">৳</span>
                                        <input type="number" step="0.01" min="0" name="discount_amount"
                                            id="discount_amount" value="{{ old('discount_amount', $sale->discount_amount) }}"
                                            class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5 text-sm font-bold text-gray-800">
                                        <p id="discount-error" class="hidden mt-1 text-sm text-red-600"></p>
                                    </div>
                                </div>

                                <div class="bg-white rounded-lg p-3 shadow-sm border border-red-200">
                                    <label class="block text-sm font-semibold text-gray-700">Extra Refunded Amount</label>
                                    <div class="mt-2 text-lg font-bold text-red-700">৳ {{ number_format((float) ($sale->extra_refunded_amount ?? 0), 2) }}</div>
                                </div>

                                <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-200">
                                    <label class="block text-sm font-semibold text-gray-700">Remaining Extra Amount</label>
                                    <div class="mt-2 text-lg font-bold text-gray-700">৳ {{ number_format((float) ($sale->extra_remaining_amount ?? 0), 2) }}</div>
                                </div>

                                <!-- Total Payable -->
                                <div class="bg-white rounded-lg p-3 shadow-sm border border-green-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="total_payable"
                                            class="block text-sm font-semibold text-gray-700">Total Payable</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="total_payable" title="Copy Total Payable">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-gray-500 mr-2">৳</span>
                                        <input type="number" step="0.01" name="total_payable" id="total_payable"
                                            readonly value="{{ old('total_payable', $sale->total_payable) }}"
                                            class="copyable-field w-full border-green-200 bg-green-50 rounded-lg shadow-sm py-1.5 px-2.5 text-sm font-bold text-green-700">
                                    </div>
                                </div>

                                <!-- Received Amount -->
                                <div class="bg-white rounded-lg p-3 shadow-sm border border-blue-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="received_amount"
                                            class="block text-sm font-semibold text-gray-700">Total Received (autofill)</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="received_amount" title="Copy Received Amount">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-gray-500 mr-2">৳</span>
                                        <input type="number" step="0.01" name="received_amount"
                                            id="received_amount" disabled
                                            value="{{ old('received_amount', $sale->received_amount) }}"
                                            class="copyable-field w-full border-blue-200 bg-blue-50 disabled:cursor-not-allowed disabled:opacity-75 rounded-lg shadow-sm py-1.5 px-2.5 text-sm font-bold text-blue-700">
                                    </div>
                                </div>

                                <!-- Due Amount -->
                                <div class="bg-white rounded-lg p-3 shadow-sm border border-red-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="due_amount" class="block text-sm font-semibold text-gray-700">Due
                                            Amount</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="due_amount" title="Copy Due Amount">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-gray-500 mr-2">৳</span>
                                        <input type="number" step="0.01" name="due_amount" id="due_amount"
                                            readonly value="{{ old('due_amount', $sale->due_amount) }}"
                                            class="copyable-field w-full border-red-200 bg-red-50 rounded-lg shadow-sm py-1.5 px-2.5 text-sm font-bold text-red-600">
                                    </div>
                                </div>

                                <div class="bg-white rounded-lg p-3 shadow-sm border border-amber-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="extra_received_amount" class="block text-sm font-semibold text-gray-700">Extra Received Amount</label>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-gray-500 mr-2">৳</span>
                                        <input type="number" step="0.01" id="extra_received_amount" readonly
                                            value="0.00"
                                            class="w-full border-amber-200 bg-amber-50 rounded-lg shadow-sm py-1.5 px-2.5 text-sm font-bold text-amber-700">
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Records Section -->
                            <div class="mt-3">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-bold text-sm text-gray-800 flex items-center">
                                        <i class="fas fa-credit-card mr-2 text-blue-600"></i>
                                        Payment Records
                                    </h4>
                                    <div class="text-sm text-gray-600 bg-gray-100 px-2.5 py-1.5 rounded-lg">
                                        Total Payments: <span id="total-payment-count"
                                            class="font-bold">{{ count($sale->payments) }}</span>
                                    </div>
                                </div>

                                <div id="payments-container" class="space-y-3">
                                    @foreach ($sale->payments as $index => $payment)
                                        <div
                                            class="payment-item bg-white rounded-lg p-3 shadow-sm border border-blue-200 hover:shadow-md transition duration-200 ease-in-out">
                                            <div class="grid grid-cols-1 md:grid-cols-5 gap-2">
                                                <!-- Payment Method -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-1">
                                                        <label
                                                            class="block text-sm font-semibold text-gray-700">Payment
                                                            Method *</label>
                                                        <button type="button"
                                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                                            data-field="payments[{{ $index }}][payment_method]"
                                                            title="Copy Payment Method">
                                                            <i class="fas fa-copy text-xs"></i>
                                                        </button>
                                                    </div>
                                                    <select name="payments[{{ $index }}][payment_method]"
                                                        required
                                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                                        <option value="">Select Method</option>
                                                        <option value="Cash"
                                                            {{ $payment->payment_method == 'Cash' ? 'selected' : '' }}>
                                                            Cash</option>
                                                        <option value="Bkash"
                                                            {{ $payment->payment_method == 'Bkash' ? 'selected' : '' }}>
                                                            Bkash</option>
                                                        <option value="Nagad"
                                                            {{ $payment->payment_method == 'Nagad' ? 'selected' : '' }}>
                                                            Nagad</option>
                                                        <option value="Bank Transfer"
                                                            {{ $payment->payment_method == 'Bank Transfer' ? 'selected' : '' }}>
                                                            Bank Transfer</option>
                                                        <option value="Card"
                                                            {{ $payment->payment_method == 'Card' ? 'selected' : '' }}>
                                                            Card</option>
                                                    </select>
                                                </div>

                                                <!-- Amount -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-1">
                                                        <label class="block text-sm font-semibold text-gray-700">Amount
                                                            *</label>
                                                        <button type="button"
                                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                                            data-field="payments[{{ $index }}][received_amount]"
                                                            title="Copy Amount">
                                                            <i class="fas fa-copy text-xs"></i>
                                                        </button>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <span class="text-gray-500 mr-2">৳</span>
                                                        <input type="number" step="0.01"
                                                            name="payments[{{ $index }}][received_amount]"
                                                            required value="{{ $payment->received_amount }}"
                                                            class="copyable-field payment-amount w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                                    </div>
                                                </div>

                                                <!-- Transaction ID -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-1">
                                                        <label
                                                            class="block text-sm font-semibold text-gray-700">Transaction
                                                            ID</label>
                                                        <button type="button"
                                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                                            data-field="payments[{{ $index }}][transaction_id]"
                                                            title="Copy Transaction ID">
                                                            <i class="fas fa-copy text-xs"></i>
                                                        </button>
                                                    </div>
                                                    <input type="text"
                                                        name="payments[{{ $index }}][transaction_id]"
                                                        value="{{ $payment->transaction_id }}"
                                                        placeholder="TRX-123456"
                                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                                </div>

                                                <!-- Payment Date & Time -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-1">
                                                        <label
                                                            class="block text-sm font-semibold text-gray-700">Payment
                                                            Date & Time *</label>
                                                        <button type="button"
                                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                                            data-field="payments[{{ $index }}][payment_datetime]"
                                                            title="Copy Payment Date & Time">
                                                            <i class="fas fa-copy text-xs"></i>
                                                        </button>
                                                    </div>
                                                    <input type="datetime-local"
                                                        name="payments[{{ $index }}][payment_datetime]"
                                                        value="{{ $payment->payment_datetime ? \Carbon\Carbon::parse($payment->payment_datetime)->format('Y-m-d\TH:i') : '' }}"
                                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                                </div>

                                                <!-- Remark -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-1">
                                                        <label
                                                            class="block text-sm font-semibold text-gray-700">Remark</label>
                                                        <button type="button"
                                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                                            data-field="payments[{{ $index }}][remark]"
                                                            title="Copy Remark">
                                                            <i class="fas fa-copy text-xs"></i>
                                                        </button>
                                                    </div>
                                                    <input type="text"
                                                        name="payments[{{ $index }}][remark]"
                                                        value="{{ $payment->remark }}" placeholder="Optional note"
                                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                                </div>
                                            </div>

                                            <!-- Payment Proof -->
                                            <div class="mt-2">
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-sm font-semibold text-gray-700">Payment
                                                        Proof</label>
                                                    @if ($payment->payment_proof)
                                                        <a href="{{ route('payments.proof', $payment) }}" target="_blank"
                                                            class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800">
                                                            <i class="fas fa-paperclip"></i> View uploaded proof
                                                        </a>
                                                    @endif
                                                </div>
                                                <input type="hidden" name="payments[{{ $index }}][payment_proof]"
                                                    value="{{ $payment->payment_proof }}">
                                                <input type="file" name="payments[{{ $index }}][proof_file]"
                                                    accept="image/*,application/pdf"
                                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 py-1.5 px-2.5 text-xs">
                                            </div>

                                            <!-- Remove Button -->
                                            <div class="flex justify-end mt-2">
                                                <button type="button"
                                                    class="bg-red-500 hover:bg-red-600 text-white py-1.5 px-2.5 rounded-lg text-sm font-semibold transition duration-200 ease-in-out transform hover:scale-105 remove-payment">
                                                    <i class="fas fa-trash mr-1"></i>Remove Payment
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Add Payment Button -->
                                <button type="button" id="add-payment"
                                    class="mt-3 w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-1.5 px-6 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-md hover:shadow-lg">
                                    <i class="fas fa-plus-circle mr-2"></i>Add Another Payment Record
                                </button>
                            </div>
                        </div>

                        <!-- Sales Information -->
                        <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-2">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-chart-line text-white text-sm"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-800">Sales Information</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="sales_source"
                                            class="block text-sm font-semibold text-gray-700">Sales Source</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="sales_source" title="Copy Sales Source">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="text" name="sales_source" id="sales_source"
                                        value="{{ old('sales_source', $sale->sales_source) }}"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="sold_by_name" class="block text-sm font-semibold text-gray-700">Sold
                                            By</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="sold_by_name" title="Copy Sold By">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="text" id="sold_by_name" value="{{ $sale->seller?->name ?? 'Not specified' }}" disabled
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm py-1.5 px-2.5 bg-gray-200 font-semibold text-gray-600 cursor-not-allowed">
                                    <input type="hidden" name="sold_by" id="sold_by" value="{{ old('sold_by', $sale->sold_by) }}">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="issued_date"
                                            class="block text-sm font-semibold text-gray-700">Issued Date</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="issued_date" title="Copy Issued Date">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="date" name="issued_date" id="issued_date"
                                        value="{{ old('issued_date', $sale->issued_date) }}"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-2">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-sticky-note text-white text-sm"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-800">Remarks</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="remark1" class="block text-sm font-semibold text-gray-700">Remark
                                            1</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="remark1" title="Copy Remark 1">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <textarea name="remark1" id="remark1" rows="3"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">{{ old('remark1', $sale->remark1) }}</textarea>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="remark2" class="block text-sm font-semibold text-gray-700">Remark
                                            2</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="remark2" title="Copy Remark 2">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <textarea name="remark2" id="remark2" rows="3"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">{{ old('remark2', $sale->remark2) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Co-Passengers -->
                        <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-2">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-users text-white text-sm"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-800">Co-Passengers</h3>
                            </div>

                            <div id="co-passengers-container" class="space-y-2">
                                @foreach ($sale->coPassengers as $index => $passenger)
                                    <div
                                        class="co-passenger-item bg-red-600  rounded-lg p-3 shadow-sm border border-blue-200 hover:shadow-md transition duration-200 ease-in-out">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-sm font-semibold text-white">Name</label>
                                                    <button type="button"
                                                        class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                                        data-field="co_passengers[{{ $index }}][name]"
                                                        title="Copy Passenger Name">
                                                        <i class="fas fa-copy text-xs"></i>
                                                    </button>
                                                </div>
                                                <input type="text" name="co_passengers[{{ $index }}][name]"
                                                    value="{{ $passenger->name }}"
                                                    class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                            </div>
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-sm font-semibold text-white">NID</label>
                                                    <button type="button"
                                                        class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                                        data-field="co_passengers[{{ $index }}][nid]"
                                                        title="Copy Passenger NID">
                                                        <i class="fas fa-copy text-xs"></i>
                                                    </button>
                                                </div>
                                                <input type="text" name="co_passengers[{{ $index }}][nid]"
                                                    value="{{ $passenger->nid }}"
                                                    class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                            </div>
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-sm font-semibold text-white">Mobile
                                                        Number</label>
                                                    <button type="button"
                                                        class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                                        data-field="co_passengers[{{ $index }}][co_passernger_number]"
                                                        title="Copy Passenger Mobile">
                                                        <i class="fas fa-copy text-xs"></i>
                                                    </button>
                                                </div>
                                                <input type="text"
                                                    name="co_passengers[{{ $index }}][co_passernger_number]"
                                                    value="{{ $passenger->co_passernger_number }}"
                                                    class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                            </div>
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="block text-sm font-semibold text-white">Date of
                                                        Birth</label>
                                                    <button type="button"
                                                        class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                                        data-field="co_passengers[{{ $index }}][date_of_birth]"
                                                        title="Copy Passenger Date of Birth">
                                                        <i class="fas fa-copy text-xs"></i>
                                                    </button>
                                                </div>
                                                <input type="date"
                                                    name="co_passengers[{{ $index }}][date_of_birth]"
                                                    value="{{ $passenger->date_of_birth }}"
                                                    class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                            </div>
                                        </div>
                                        <button type="button"
                                            class="mt-3 bg-red-500 hover:bg-red-600 text-white py-1.5 px-2.5 rounded-lg text-sm font-semibold transition duration-200 ease-in-out transform hover:scale-105 remove-passenger">
                                            <i class="fas fa-user-times mr-1"></i>Remove Passenger
                                        </button>
                                    </div>
                                @endforeach
                            </div>

                            <button type="button" id="add-passenger"
                                class="mt-2 bg-green-500 hover:bg-green-600 text-white font-bold py-1.5 px-6 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-md">
                                <i class="fas fa-user-plus mr-2"></i>Add Co-Passenger
                            </button>
                        </div>
                        <!-- PDF Section -->
                        <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100 mt-3">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-bold text-sm text-gray-800">PDF Files</h3>
                                <button type="button" id="add-additional-pdf"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1.5 px-3 rounded-md">
                                    <i class="fas fa-plus mr-1"></i>Add PDF Field
                                </button>
                            </div>
                            <div id="additional-pdf-fields" class="space-y-2 mb-3"></div>

                            <!-- Existing PDF Files -->
                            @if ($sale->printedTickets->count() > 0)
                                <div class="mb-3">
                                    <h4 class="font-bold text-sm text-gray-800 mb-2 flex items-center">
                                        <i class="fas fa-list mr-2 text-blue-600"></i>
                                        Existing PDF Files
                                    </h4>

                                    <div id="existing-pdfs-container" class="space-y-2">
                                        @foreach ($sale->printedTickets as $index => $ticket)
                                            <div
                                                class="existing-pdf-item bg-white rounded-lg p-4 shadow-sm border border-blue-200 hover:shadow-md transition duration-200 ease-in-out">
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                                    <div>
                                                        <div class="flex items-center justify-between mb-1">
                                                            <label class="block text-sm font-semibold text-gray-700">
                                                                PDF-{{ $index + 1 }} Filename
                                                            </label>
                                                            <button type="button"
                                                                class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                                                data-field="existing_pdf_{{ $ticket->id }}"
                                                                title="Copy Filename">
                                                                <i class="fas fa-copy text-xs"></i>
                                                            </button>
                                                        </div>
                                                        <input type="text" id="existing_pdf_{{ $ticket->id }}"
                                                            value="{{ $ticket->filename }}" readonly
                                                            class="copyable-field w-full border-gray-300 rounded-lg shadow-sm py-1.5 px-2.5 bg-gray-50">
                                                    </div>

                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif


                        </div>

                        @if ($sale->shipment || in_array($sale->status, ['shipped', 'ticket-printed', 'shipment_id_entered'], true))
                            <!-- Shipment Info Section -->
                            <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100">
                                <div class="flex items-center mb-2">
                                    <div class="bg-red-600 p-2 rounded-lg mr-3">
                                        <i class="fas fa-truck text-white text-sm"></i>
                                    </div>
                                    <h3 class="text-base font-bold text-red-800">
                                        {{ $sale->shipment ? 'Shipment Details' : 'Add Shipment Info' }}
                                    </h3>
                                </div>
                                <div>
                                    <input type="text" name="shipment_id"
                                        value="{{ $sale->shipment->shipment_id ?? '' }}"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                </div>
                            </div>
                        @endif

                        <!-- Submit Button -->
                        <div class="mt-3 flex justify-end space-x-4">
                            <a href="/sales/status/pending"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-1.5 px-4 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-md">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>

                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-4 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl">
                                <i class="fas fa-save mr-2"></i>Update Ticket Sale
                            </button>
                        </div>


                    </form>

                    <!-- Verification Status Section -->
                    @if ($sale->verifyby && count($sale->verifyby) > 0)
                        <div class="bg-green-50 rounded-lg p-3 shadow-sm border border-green-200 my-8">
                            <div class="flex items-center mb-2">
                                <div class="bg-green-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-check-circle text-white text-sm"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-800">Verification Status</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach ($sale->verifyby as $verification)
                                    <div class="bg-white rounded-lg p-4 shadow-sm border border-green-200">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-semibold 
                                            {{ $verification->name == 'payment-verified' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $verification->name == 'ticket-issued' ? 'bg-blue-100 text-blue-800' : '' }}
                                             {{ $verification->name == 'ticket-printed' ? 'bg-blue-100 text-blue-800' : '' }}
                                               {{ $verification->name == 'shipped' ? 'bg-red-100 text-red-800' : '' }}"
                                                    {{ $verification->name == 'Shipment_id_entered' ? 'bg-blue-100 text-blue-800' : '' }}"
                                                    {{ $verification->name == 'cancelled' ? 'bg-red-100 text-red-800' : '' }}>
                                                    {{ ucfirst(str_replace('-', ' ', $verification->name)) }}
                                                </span>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    Verified by: <span class="font-semibold">
                                                        {{ $verification->verifiedByUser->name ?? 'N/A' }}</span>
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs text-gray-500">
                                                    {{ $verification->created_at->format('M d, Y h:i A') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="copyToast"
        class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-1.5 rounded-lg shadow-lg transform translate-y-full transition-transform duration-300 z-50">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span id="toastMessage">Data copied to clipboard!</span>
        </div>
    </div>



    

    
    


    <div id="editSaleConfig"
        data-passenger-count="{{ count($sale->coPassengers) }}"
        data-payment-count="{{ count($sale->payments) }}"
        data-pdf-prefix="{{ $pdfFilenamePrefix }}"
        data-next-pdf-number="{{ $nextPdfNumber }}"
        data-maximum-birth-date="{{ now()->subYears(18)->format('Y-m-d') }}"></div>

    @vite(['resources/js/pages/edit-sale.js'])
</x-app-layout>


