<x-app-layout>

    <div class="flex justify-between items-center mt-2 ml-5">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <i class="fas fa-edit mr-2 text-blue-600"></i>
            Ship Ticket Sale #{{ $sale->id }}

        </h2>
        <a href="/sales/status/{{ $sale->status }}"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5">
            <i class="fas fa-arrow-left mr-2"></i> Back to List
        </a>

    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 ">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl">
                <div class="">
                    @if ($errors->any())
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-8 shadow-sm">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-red-800 font-semibold">Whoops! There were some problems with your
                                        input.</h3>
                                    <ul class="mt-2 text-red-700 list-disc list-inside text-sm">
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

                        <!-- Customer Information -->
                        <div class="bg-blue-50 rounded-2xl p-6 shadow-sm border border-blue-100">
                            <div class="flex items-center justify-between  ">
                                <div class="flex items-center mb-4">
                                    <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                        <i class="fas fa-user text-white text-sm"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800">Customer Information</h3>
                                </div>

                                @if ($sale->status == 'ticket-issued')
                                    <a href="{{ route('print.pdf', $sale->id) }}" target="_blank"
                                        class="px-4 py-2 bg-blue-950 text-white rounded">
                                        Ticket
                                    </a>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="customer_name"
                                            class="block text-sm font-semibold text-gray-700">Customer ID *</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="id" title="Copy Customer id">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="text" name="id" id="id" required
                                        value="{{ old('id', $sale->id) }}"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>
                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
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
                                        class="copyable-field bg-red-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>

                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
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
                                        class="copyable-field w-full bg-red-500 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-2">
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
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>

                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
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
                                        class="copyable-field bg-red-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>


                                <input type="number" value={{ $nextSale->id ?? '' }} name="next_sale_id" hidden>


                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
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
                                        class="copyable-field bg-red-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>

                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="date_of_birth"
                                            class="block text-sm font-semibold text-gray-700">Date of Birth</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="date_of_birth" title="Copy Date of Birth">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="date" name="date_of_birth" id="date_of_birth"
                                        value="{{ old('date_of_birth', $sale->date_of_birth) }}"
                                        class="copyable-field bg-red-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>

                                <div class="md:col-span-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="address"
                                            class="block text-sm font-semibold text-gray-700">Address</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="address" title="Copy Address">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <textarea name="address" id="address" rows="3"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">{{ old('address', $sale->address) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Ticket Information -->
                        <div class="bg-blue-50 rounded-2xl p-6 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-4">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-ticket-alt text-white text-sm"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-800">Ticket Information</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="ship_id" class="block text-sm font-semibold text-gray-700">Ship
                                            *</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="ship_id" title="Copy Ship">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <select name="ship_id" id="ship_id" required
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
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
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="company_id"
                                            class="block text-sm font-semibold text-gray-700">Company *</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="company_id" title="Copy Company">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <select name="company_id" id="company_id" required
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
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
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="journey_date"
                                            class="block text-sm font-semibold text-gray-700">Journey Date *</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="journey_date" title="Copy Journey Date">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="date" name="journey_date" id="journey_date"
                                        value="{{ old('journey_date', $sale->journey_date) }}"
                                        class="copyable-field bg-red-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>

                                <div class="bg-red-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
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
                                        class="copyable-field bg-red-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>

                                <div class="bg-green-500 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="number_of_ticket"
                                            class="block text-sm font-semibold text-gray-700">Number of Tickets
                                            *</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="number_of_ticket" title="Copy Number of Tickets">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="number" name="number_of_ticket" id="number_of_ticket" required
                                        min="1" value="{{ old('number_of_ticket', $sale->number_of_ticket) }}"
                                        class="copyable-field bg-green-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>


                            </div>
                        </div>

                        <!-- Package Selection -->
                        <div class="bg-blue-50 rounded-2xl p-6 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-6">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-boxes text-white text-sm"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-800">Package Selection</h3>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Departure Packages -->
                                <div class="bg-white rounded-xl p-6 shadow-sm border border-blue-200">
                                    <h4 class="font-bold text-lg text-blue-800 mb-4 flex items-center">
                                        <i class="fas fa-ship mr-2 text-blue-600"></i>
                                        Departure Packages
                                    </h4>
                                    <div class="space-y-3">
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
                                                    <input type="radio" name="departure_package"
                                                        value="{{ $package->id }}"
                                                        id="departure_package_{{ $package->id }}"
                                                        {{ $departureCategory ? 'checked' : '' }}
                                                        class="copyable-field focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300">
                                                    <label for="departure_package_{{ $package->id }}"
                                                        class="ml-3 block text-sm font-medium text-gray-700">
                                                        <span class="font-semibold">{{ $package->name }}</span>
                                                        <span
                                                            class="text-blue-600 font-bold ml-2">৳{{ number_format($package->price, 2) }}</span>
                                                    </label>
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
                                                        class="w-20 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3 text-center">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Return Packages -->
                                <div class="bg-white rounded-xl p-6 shadow-sm border border-blue-200">
                                    <h4 class="font-bold text-lg text-blue-800 mb-4 flex items-center">
                                        <i class="fas fa-undo-alt mr-2 text-blue-600"></i>
                                        Return Packages
                                    </h4>
                                    <div class="space-y-3">
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
                                                    <input type="radio" name="return_package"
                                                        value="{{ $package->id }}"
                                                        id="return_package_{{ $package->id }}"
                                                        {{ $returnCategory ? 'checked' : '' }}
                                                        class="copyable-field focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300">
                                                    <label for="return_package_{{ $package->id }}"
                                                        class="ml-3 block text-sm font-medium text-gray-700">
                                                        <span class="font-semibold">{{ $package->name }}</span>
                                                        <span class="text-blue-600 font-bold ml-2">
                                                            ৳{{ number_format($package->round_trip_price - $package->price, 2) }}
                                                        </span>

                                                    </label>
                                                </div>
                                                <div class="flex items-center justify-end space-x-2">
                                                    <label for="return_quantity_{{ $package->id }}"
                                                        class="text-sm font-semibold text-gray-700">
                                                        Quantity:
                                                    </label>
                                                    <input type="number" name="return_quantity[{{ $package->id }}]"
                                                        id="return_quantity_{{ $package->id }}"
                                                        value="{{ $returnQuantity }}" min="0"
                                                        class="w-20 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3 text-center">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Information -->
                        <div class="bg-blue-50 rounded-2xl p-6 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-6">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-money-bill-wave text-white text-sm"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-800">Financial Summary</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                                <!-- Ticket Fee -->
                                <div class="bg-white rounded-xl p-6 shadow-sm border border-blue-200">
                                    <div class="flex items-center justify-between mb-2">
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
                                        <input type="number" step="0.01" name="ticket_fee" id="ticket_fee"
                                            required value="{{ old('ticket_fee', $sale->ticket_fee) }}"
                                            class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4 text-lg font-bold text-gray-800">
                                    </div>
                                </div>

                                <!-- Other Fee -->
                                <div class="bg-white rounded-xl p-6 shadow-sm border border-blue-200">
                                    <div class="flex items-center justify-between mb-2">
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
                                            class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4 text-lg font-bold text-gray-800">
                                    </div>
                                </div>

                                <!-- Total Payable -->
                                <div class="bg-white rounded-xl p-6 shadow-sm border border-green-200">
                                    <div class="flex items-center justify-between mb-2">
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
                                            class="copyable-field w-full border-green-200 bg-green-50 rounded-lg shadow-sm py-3 px-4 text-lg font-bold text-green-700">
                                    </div>
                                </div>

                                <!-- Received Amount -->
                                <div class="bg-white rounded-xl p-6 shadow-sm border border-blue-200">
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="received_amount"
                                            class="block text-sm font-semibold text-gray-700">Total Received</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="received_amount" title="Copy Received Amount">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-gray-500 mr-2">৳</span>
                                        <input type="number" step="0.01" name="received_amount"
                                            id="received_amount" readonly
                                            value="{{ old('received_amount', $sale->received_amount) }}"
                                            class="copyable-field w-full border-blue-200 bg-blue-50 rounded-lg shadow-sm py-3 px-4 text-lg font-bold text-blue-700">
                                    </div>
                                </div>

                                <!-- Due Amount -->
                                <div class="bg-white rounded-xl p-6 shadow-sm border border-red-200">
                                    <div class="flex items-center justify-between mb-2">
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
                                            class="copyable-field w-full border-red-200 bg-red-50 rounded-lg shadow-sm py-3 px-4 text-lg font-bold text-red-600">
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Records Section -->
                            <div class="mt-8">
                                <div class="flex items-center justify-between mb-6">
                                    <h4 class="font-bold text-lg text-gray-800 flex items-center">
                                        <i class="fas fa-credit-card mr-2 text-blue-600"></i>
                                        Payment Records
                                    </h4>
                                    <div class="text-sm text-gray-600 bg-gray-100 px-4 py-2 rounded-lg">
                                        Total Payments: <span id="total-payment-count"
                                            class="font-bold">{{ count($sale->payments) }}</span>
                                    </div>
                                </div>

                                <div id="payments-container" class="space-y-6">
                                    @foreach ($sale->payments as $index => $payment)
                                        <div
                                            class="payment-item bg-white rounded-xl p-6 shadow-sm border border-blue-200 hover:shadow-md transition duration-200 ease-in-out">
                                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                                <!-- Payment Method -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-2">
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
                                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
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
                                                    <div class="flex items-center justify-between mb-2">
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
                                                            class="copyable-field payment-amount w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                                                    </div>
                                                </div>

                                                <!-- Transaction ID -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-2">
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
                                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                                                </div>

                                                <!-- Payment Date & Time -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-2">
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
                                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                                                </div>

                                                <!-- Remark -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-2">
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
                                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                                                </div>
                                            </div>

                                            <!-- Remove Button -->
                                            <div class="flex justify-end mt-4">
                                                <button type="button"
                                                    class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg text-sm font-semibold transition duration-200 ease-in-out transform hover:scale-105 remove-payment">
                                                    <i class="fas fa-trash mr-1"></i>Remove Payment
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Add Payment Button -->
                                <button type="button" id="add-payment"
                                    class="mt-6 w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold py-3 px-6 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-md hover:shadow-lg">
                                    <i class="fas fa-plus-circle mr-2"></i>Add Another Payment Record
                                </button>
                            </div>
                        </div>

                        <!-- Sales Information -->
                        <div class="bg-blue-50 rounded-2xl p-6 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-4">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-chart-line text-white text-sm"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-800">Sales Information</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
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
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="sold_by" class="block text-sm font-semibold text-gray-700">Sold
                                            By</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="sold_by" title="Copy Sold By">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <input type="text" name="sold_by" id="sold_by"
                                        value="{{ old('sold_by', $sale->sold_by) }}"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-2">
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
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="status"
                                            class="block text-sm font-semibold text-gray-700">Status</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="status" title="Copy Status">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <select name="status" id="status"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">
                                        <option value="pending" {{ $sale->status == 'pending' ? 'selected' : '' }}>
                                            Pending</option>
                                        <option value="payment-verified"
                                            {{ $sale->status == 'payment-verified' ? 'selected' : '' }}>
                                            payment-verified</option>
                                        <option value="ticket-issued"
                                            {{ $sale->status == 'ticket-issued' ? 'selected' : '' }}>ticket-issued
                                        </option>
                                        <option value="ticket-printed"
                                            {{ $sale->status == 'ticket-printed' ? 'selected' : '' }}>ticket-printed
                                        </option>
                                        <option value="shipment_id_entered"
                                            {{ $sale->status == 'shipment_id_entered' ? 'selected' : '' }}>Parcel
                                            Created</option>
                                        <option value="shipped" {{ $sale->status == 'shipped' ? 'selected' : '' }}>
                                            shipped
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="bg-blue-50 rounded-2xl p-6 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-4">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-sticky-note text-white text-sm"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-800">Remarks</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="remark1" class="block text-sm font-semibold text-gray-700">Remark
                                            1</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="remark1" title="Copy Remark 1">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <textarea name="remark1" id="remark1" rows="3"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">{{ old('remark1', $sale->remark1) }}</textarea>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label for="remark2" class="block text-sm font-semibold text-gray-700">Remark
                                            2</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="remark2" title="Copy Remark 2">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <textarea name="remark2" id="remark2" rows="3"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-3 px-4">{{ old('remark2', $sale->remark2) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Co-Passengers -->
                        <div class="bg-blue-50 rounded-2xl p-6 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-4">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-users text-white text-sm"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-800">Co-Passengers</h3>
                            </div>

                            <div id="co-passengers-container" class="space-y-4">
                                @foreach ($sale->coPassengers as $index => $passenger)
                                    <div
                                        class="co-passenger-item bg-red-600  rounded-xl p-6 shadow-sm border border-blue-200 hover:shadow-md transition duration-200 ease-in-out">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <div class="flex items-center justify-between mb-2">
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
                                                    class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                                            </div>
                                            <div>
                                                <div class="flex items-center justify-between mb-2">
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
                                                    class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                                            </div>
                                            <div>
                                                <div class="flex items-center justify-between mb-2">
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
                                                    class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                                            </div>
                                            <div>
                                                <div class="flex items-center justify-between mb-2">
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
                                                    class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                                            </div>
                                        </div>
                                        <button type="button"
                                            class="mt-3 bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg text-sm font-semibold transition duration-200 ease-in-out transform hover:scale-105 remove-passenger">
                                            <i class="fas fa-user-times mr-1"></i>Remove Passenger
                                        </button>
                                    </div>
                                @endforeach
                            </div>

                            <button type="button" id="add-passenger"
                                class="mt-4 bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-md">
                                <i class="fas fa-user-plus mr-2"></i>Add Co-Passenger
                            </button>
                        </div>
                        @if ($sale->status == 'payment-verified')
                            @php $count = $number + 1; @endphp
                            <div class="bg-blue-950 rounded-2xl p-6">
                                <div id="pdf-fields"
                                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4  shadow-sm mt-6">

                                    @for ($i = 1; $i <= $totalDepartureTickets; $i += 5)
                                        <div class="pdf-item mb-4 border p-3 rounded-lg relative">
                                            <div class="flex items-center justify-between m-2">
                                                <label for="pdf-{{ $i }}"
                                                    class="text-sm font-semibold text-gray-100">
                                                    Pdf-{{ $count }}
                                                </label>

                                                <div class="flex gap-2">
                                                    <button type="button" class="copy-field-btn text-blue-600"
                                                        data-field="pdf-{{ $i }}">
                                                        <i class="fas fa-copy text-xs"></i>
                                                    </button>
                                                </div>

                                                <button type="button" class="remove-pdf-btn text-red-600"
                                                    title="Remove">
                                                    <i class="fas fa-times text-xs"></i>
                                                </button>
                                            </div>

                                            <input type="text" id="pdf-{{ $i }}" readonly
                                                name="pdf[{{ $i }}]"
                                                value="{{ ($sale->whatsapp ?? 'whatsapp') . '-' . $count }}"
                                                class="copyable-field w-full border-gray-300 rounded-lg py-2 px-3">
                                        </div>

                                        @php $count++; @endphp
                                    @endfor

                                </div>

                                <div class="">
                                    <button type="button" id="addPdfField"
                                        class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg rounded-lg hover:bg-blue-700">
                                        + Add New PDF Field
                                    </button>
                                </div>
                            </div>
                        @endif



                        <!-- PDF Section -->
                        <div class="bg-blue-50 rounded-2xl p-6 shadow-sm border border-blue-100 mt-6">

                            <!-- Existing PDF Files -->
                            @if ($sale->printedTickets->count() > 0)
                                <div class="mb-8">
                                    <h4 class="font-bold text-lg text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-list mr-2 text-blue-600"></i>
                                        Existing PDF Files
                                    </h4>

                                    <div id="existing-pdfs-container" class="space-y-4">
                                        @foreach ($sale->printedTickets as $index => $ticket)
                                            <div
                                                class="existing-pdf-item bg-white rounded-xl p-4 shadow-sm border border-blue-200 hover:shadow-md transition duration-200 ease-in-out">
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                    <div>
                                                        <div class="flex items-center justify-between mb-2">
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
                                                            class="copyable-field w-full border-gray-300 rounded-lg shadow-sm py-2 px-3 bg-gray-50">
                                                    </div>

                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif


                        </div>

                        <!-- ADD MORE PDF FIELDS SECTION -->
                        @if ($sale->status != 'pending' && $sale->status != 'payment-verified')
                            <div class="bg-yellow-50 rounded-2xl p-6 shadow-sm border border-yellow-200 mt-6">
                                <div class="flex items-center mb-4">
                                    <div class="bg-yellow-600 p-2 rounded-lg mr-3">
                                        <i class="fas fa-file-pdf text-white text-sm"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-gray-800">Add More PDF Fields</h3>
                                </div>

                                <div class="mb-6">
                                    <p class="text-gray-600 mb-4">Add more PDF filename fields. Format:
                                        {{ $sale->whatsapp ?? 'whatsapp' }}-{number}</p>

                                    @php
                                        $existingPdfCount = $sale->printedTickets->count();
                                        $nextPdfNumber = $existingPdfCount + 1;
                                    @endphp

                                    <div id="additional-pdf-fields" class="space-y-4">
                                        <!-- Additional PDF fields will be added here -->
                                    </div>

                                    <button type="button" id="add-additional-pdf"
                                        class="mt-4 bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-md">
                                        <i class="fas fa-plus-circle mr-2"></i>Add PDF Field
                                    </button>
                                </div>
                            </div>
                        @endif


                        @if ($sale->status == 'payment-verified')
                            <div class="bg-yellow-50 rounded-2xl p-6 shadow-sm border border-yellow-200 mt-6">

                                <div class="flex items-center mb-4">
                                    <div class="bg-yellow-600 p-2 rounded-lg mr-3">
                                        <i class="fas fa-exclamation-triangle text-white text-sm"></i>
                                    </div>

                                    <h3 class="text-xl font-bold text-gray-800">
                                        Important Notice
                                    </h3>
                                </div>

                                <p class="text-gray-700 text-sm leading-relaxed">
                                    Tickets PDF document has already been generated using this WhatsApp number.
                                    Please review the existing document before requesting a new one.
                                </p>
                                @if ($groupByStatus)
                                    <p class="font-bold text-xl">Do You Want to group tickets:</p>
                                    <div class="flex justify-around">
                                        <div>
                                            <input type="radio" id="group_tickets_yes" name="group_tickets"
                                                value="yes">
                                            <label for="group_tickets_yes">Yes</label><br>
                                        </div>
                                        <div>
                                            <input type="radio" id="group_tickets_no" name="group_tickets"
                                                value="no" checked>
                                            <label for="group_tickets_no">No</label><br>
                                        </div>
                                    </div>

                                    <div>
                                        <input type="hidden" name="group_by_id" value="{{ $groupById }}">
                                    </div>
                                @endif

                            </div>
                        @endif

                        @if ($sale->status == 'shipped' || $sale->status == 'ticket-printed' || $sale->status == 'shipment_id_entered')
                            <!-- Shipment Info Section -->
                            <div class="bg-blue-50 rounded-2xl p-6 shadow-sm border border-blue-100">
                                <div class="flex items-center mb-4">
                                    <div class="bg-red-600 p-2 rounded-lg mr-3">
                                        <i class="fas fa-truck text-white text-sm"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-red-800">Add Shipment Info</h3>
                                </div>
                                <div>
                                    <input type="text" name="shipment_id"
                                        value="{{ $sale->shipment->shipment_id ?? '' }}"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                                </div>
                            </div>
                        @endif

                        <!-- Submit Button -->
                        <div class="mt-8 flex justify-end space-x-4">
                            <a href="/sales/status/pending"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-8 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-md">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>

                            <!-- Regular Update Button -->
                            <button type="submit" name="action" value="update"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl">
                                <i class="fas fa-save mr-2"></i>Update Ticket Sale
                            </button>

                            <!-- Update and Next Button -->
                            @if ($nextSale && $sale->status == 'payment-verified')
                                <button type="submit" name="action" value="update_and_next"
                                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl">
                                    <i class="fas fa-save mr-2"></i>
                                    <i class="fas fa-arrow-right mr-2"></i>
                                    Verify & Next
                                </button>
                            @endif

                            @if ($sale->status != 'payment-verified' && $sale->status != 'pending')
                                <button type="submit" name="action" value="update_and_reverify"
                                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl">
                                    <i class="fas fa-save mr-2"></i>
                                    <i class="fas fa-arrow-right mr-2"></i>
                                    Update & Re-verify
                                </button>
                            @endif

                        </div>


                    </form>

                    <!-- Verification Status Section -->
                    @if ($sale->verifyby && count($sale->verifyby) > 0)
                        <div class="bg-green-50 rounded-2xl p-6 shadow-sm border border-green-200 my-8">
                            <div class="flex items-center mb-4">
                                <div class="bg-green-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-check-circle text-white text-sm"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-800">Verification Status</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($sale->verifyby as $verification)
                                    <div class="bg-white rounded-xl p-4 shadow-sm border border-green-200">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span
                                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold 
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
        class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-y-full transition-transform duration-300 z-50">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span id="toastMessage">Data copied to clipboard!</span>
        </div>
    </div>



    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add co-passenger functionality
            let passengerIndex = {{ count($sale->coPassengers) }};
            let paymentIndex = {{ count($sale->payments) }};

            // For additional PDF fields
            let additionalPdfIndex = 0;
            const whatsappNumber = "{{ $sale->whatsapp ?? 'whatsapp' }}";
            const existingPdfCount = {{ $sale->printedTickets->count() }};
            let currentPdfNumber = existingPdfCount + 1;

            document.getElementById('add-passenger').addEventListener('click', function() {
                const container = document.getElementById('co-passengers-container');
                const newPassenger = document.createElement('div');
                newPassenger.className =
                    'co-passenger-item bg-white rounded-xl p-6 shadow-sm border border-blue-200 hover:shadow-md transition duration-200 ease-in-out';
                newPassenger.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-gray-700">Name</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="co_passengers[${passengerIndex}][name]" title="Copy Passenger Name">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="text" name="co_passengers[${passengerIndex}][name]" 
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-gray-700">NID</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="co_passengers[${passengerIndex}][nid]" title="Copy Passenger NID">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="text" name="co_passengers[${passengerIndex}][nid]" 
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-gray-700">Mobile Number</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="co_passengers[${passengerIndex}][co_passernger_number]" title="Copy Passenger Mobile">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="text" name="co_passengers[${passengerIndex}][co_passernger_number]" 
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-gray-700">Date of Birth</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="co_passengers[${passengerIndex}][date_of_birth]" title="Copy Passenger Date of Birth">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="date" name="co_passengers[${passengerIndex}][date_of_birth]" 
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                        </div>
                    </div>
                    <button type="button" class="mt-3 bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg text-sm font-semibold transition duration-200 ease-in-out transform hover:scale-105 remove-passenger">
                        <i class="fas fa-user-times mr-1"></i>Remove Passenger
                    </button>
                `;
                container.appendChild(newPassenger);
                passengerIndex++;
            });

            // Add payment functionality
            document.getElementById('add-payment').addEventListener('click', function() {
                const container = document.getElementById('payments-container');
                const newPayment = document.createElement('div');
                newPayment.className =
                    'payment-item bg-white rounded-xl p-6 shadow-sm border border-blue-200 hover:shadow-md transition duration-200 ease-in-out';
                newPayment.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-gray-700">Payment Method *</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="payments[${paymentIndex}][payment_method]" title="Copy Payment Method">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <select name="payments[${paymentIndex}][payment_method]" required
                                    class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                                <option value="">Select Method</option>
                                <option value="Cash">Cash</option>
                                <option value="Bkash">Bkash</option>
                                <option value="Nagad">Nagad</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Card">Card</option>
                            </select>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-gray-700">Amount *</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="payments[${paymentIndex}][received_amount]" title="Copy Amount">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <div class="flex items-center">
                                <span class="text-gray-500 mr-2">৳</span>
                                <input type="number" step="0.01" name="payments[${paymentIndex}][received_amount]" required
                                       class="copyable-field payment-amount w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-gray-700">Transaction ID</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="payments[${paymentIndex}][transaction_id]" title="Copy Transaction ID">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="text" name="payments[${paymentIndex}][transaction_id]"
                                   placeholder="TRX-123456"
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-gray-700">Payment Date & Time *</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="payments[${paymentIndex}][payment_datetime]" title="Copy Payment Date & Time">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="datetime-local" name="payments[${paymentIndex}][payment_datetime]" required
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-gray-700">Remark</label>
                                <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="payments[${paymentIndex}][remark]" title="Copy Remark">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <input type="text" name="payments[${paymentIndex}][remark]"
                                   placeholder="Optional note"
                                   class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-2 px-3">
                        </div>
                    </div>
                    <div class="flex justify-end mt-4">
                        <button type="button" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg text-sm font-semibold transition duration-200 ease-in-out transform hover:scale-105 remove-payment">
                            <i class="fas fa-trash mr-1"></i>Remove Payment
                        </button>
                    </div>
                `;
                container.appendChild(newPayment);
                paymentIndex++;
                updatePaymentCount();

                // Set current datetime for the new payment only
                const datetimeInput = newPayment.querySelector('input[type="datetime-local"]');
                const now = new Date();
                const timezoneOffset = now.getTimezoneOffset() * 60000;
                const localISOTime = new Date(now - timezoneOffset).toISOString().slice(0, 16);
                datetimeInput.value = localISOTime;

                // Trigger calculation for new payment
                calculateFinancials();
            });

            // Add additional PDF field functionality
            const addAdditionalPdfBtn = document.getElementById('add-additional-pdf');
            if (addAdditionalPdfBtn) {
                addAdditionalPdfBtn.addEventListener('click', function() {
                    const container = document.getElementById('additional-pdf-fields');
                    const newPdfField = document.createElement('div');
                    newPdfField.className =
                        'additional-pdf-item bg-white rounded-xl p-4 shadow-sm border border-yellow-200 hover:shadow-md transition duration-200 ease-in-out';
                    newPdfField.innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-semibold text-gray-700">
                                        Additional PDF-${additionalPdfIndex + 1} *
                                    </label>
                                    <button type="button" class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200" data-field="additional_pdf_${additionalPdfIndex}" title="Copy PDF Name">
                                        <i class="fas fa-copy text-xs"></i>
                                    </button>
                                </div>
                                <input type="text" 
                                       name="additional_pdf[${additionalPdfIndex}]" 
                                       id="additional_pdf_${additionalPdfIndex}"
                                       value="${whatsappNumber}-${currentPdfNumber}.pdf"
                                       readonly
                                       class="copyable-field w-full border-gray-300 rounded-lg shadow-sm py-2 px-3 bg-gray-50">
                            </div>
                            <div class="flex items-end">
                                <button type="button" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg text-sm font-semibold transition duration-200 ease-in-out transform hover:scale-105 remove-additional-pdf">
                                    <i class="fas fa-trash mr-1"></i>Remove Field
                                </button>
                            </div>
                        </div>
                        <div class="mt-2 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Filename: ${whatsappNumber}-${currentPdfNumber}.pdf
                        </div>
                    `;
                    container.appendChild(newPdfField);
                    additionalPdfIndex++;
                    currentPdfNumber++;
                });
            }

            // Remove additional PDF field
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-additional-pdf')) {
                    e.target.closest('.additional-pdf-item').remove();
                    // Don't decrement currentPdfNumber to maintain sequence
                }
            });

            // Calculate all financial values
            function calculateFinancials() {
                // Calculate total received amount from all payments
                let totalReceived = 0;
                document.querySelectorAll('.payment-amount').forEach(input => {
                    const value = parseFloat(input.value) || 0;
                    totalReceived += value;
                });

                // Update received amount field
                const receivedAmountInput = document.getElementById('received_amount');
                receivedAmountInput.value = totalReceived.toFixed(2);

                // Get ticket fee and other fee
                const ticketFee = parseFloat(document.getElementById('ticket_fee').value) || 0;
                const otherFee = parseFloat(document.getElementById('other_fee').value) || 0;

                // Calculate total payable (ticket fee + other fee)
                const totalPayable = ticketFee + otherFee;
                document.getElementById('total_payable').value = totalPayable.toFixed(2);

                // Calculate due amount (total payable - total received)
                const dueAmount = totalPayable - totalReceived;
                document.getElementById('due_amount').value = dueAmount.toFixed(2);

                // Update payment count
                updatePaymentCount();
            }

            // Update payment count
            function updatePaymentCount() {
                const paymentCount = document.querySelectorAll('.payment-item').length;
                document.getElementById('total-payment-count').textContent = paymentCount;
            }

            // Event listeners for financial calculations
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('payment-amount') ||
                    e.target.id === 'ticket_fee' ||
                    e.target.id === 'other_fee') {
                    calculateFinancials();
                }
            });

            // Remove functionality
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-passenger')) {
                    e.target.closest('.co-passenger-item').remove();
                }
                if (e.target.classList.contains('remove-payment')) {
                    e.target.closest('.payment-item').remove();
                    calculateFinancials();
                }
            });

            // Copy functionality
            function showToast(message) {
                const toast = document.getElementById('copyToast');
                const toastMessage = document.getElementById('toastMessage');
                toastMessage.textContent = message;
                toast.classList.remove('translate-y-full');

                setTimeout(() => {
                    toast.classList.add('translate-y-full');
                }, 3000);
            }

            // Copy individual field
            document.addEventListener('click', function(e) {
                if (e.target.closest('.copy-field-btn')) {
                    const copyBtn = e.target.closest('.copy-field-btn');
                    const fieldId = copyBtn.dataset.field;

                    // Find the corresponding input/select/textarea field
                    let field;
                    if (fieldId.includes('[') && fieldId.includes(']')) {
                        // Handle array fields like payments[0][amount]
                        const fieldName = fieldId.replace(/\[(\d+)\]/g, '[$1]');
                        field = document.querySelector(`[name="${fieldName}"]`);
                    } else {
                        field = document.getElementById(fieldId);
                    }

                    if (field) {
                        let valueToCopy;

                        if (field.tagName === 'SELECT') {
                            valueToCopy = field.options[field.selectedIndex].text;
                        } else if (field.type === 'radio' || field.type === 'checkbox') {
                            if (field.checked) {
                                valueToCopy = field.nextElementSibling?.textContent?.trim() || field.value;
                            } else {
                                valueToCopy = '';
                            }
                        } else {
                            valueToCopy = field.value;
                        }

                        if (valueToCopy && valueToCopy.trim() !== '') {
                            navigator.clipboard.writeText(valueToCopy).then(() => {
                                const fieldLabel = copyBtn.closest('div').querySelector('label')
                                    .textContent.replace('*', '').trim();
                                showToast(`Copied: ${fieldLabel}`);
                            }).catch(err => {
                                console.error('Failed to copy: ', err);
                                showToast('Failed to copy field');
                            });
                        } else {
                            showToast('No data to copy');
                        }
                    }
                }
            });

            // Initial calculation
            calculateFinancials();

            // Initialize PDF fields from payment-verified section (only if it exists)
            @if ($sale->status == 'payment-verified')
                let pdfIndex = {{ $count - 1 }};
                const container = document.getElementById('pdf-fields');
                if (container && document.getElementById('addPdfField')) {
                    document.getElementById('addPdfField').addEventListener('click', () => {
                        pdfIndex++;

                        const div = document.createElement('div');
                        div.className = 'pdf-item mb-4 border p-3 rounded-lg relative';

                        div.innerHTML = `
                            <div class="flex items-center justify-between mb-2">
                                <label for="pdf-${pdfIndex}" class="text-sm font-semibold text-gray-100">
                                    Pdf-${pdfIndex}
                                </label>

                                <div class="flex gap-2">
                                    <button type="button"
                                        class="copy-field-btn text-blue-600"
                                        data-field="pdf-${pdfIndex}">
                                        <i class="fas fa-copy text-xs"></i>
                                    </button>

                                    <button type="button"
                                        class="remove-pdf-btn text-red-600">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <input
                                type="text"
                                id="pdf-${pdfIndex}"
                                name="pdf[${pdfIndex}]"
                                readonly
                                value="${whatsappNumber}-${pdfIndex}"
                                class="copyable-field w-full border-gray-300 rounded-lg py-2 px-3"
                            >
                        `;

                        container.appendChild(div);
                    });

                    container.addEventListener('click', (e) => {
                        if (e.target.closest('.remove-pdf-btn')) {
                            e.target.closest('.pdf-item').remove();
                        }

                        if (e.target.closest('.copy-field-btn')) {
                            const btn = e.target.closest('.copy-field-btn');
                            const input = document.getElementById(btn.dataset.field);
                            input.select();
                            document.execCommand('copy');
                        }
                    });
                }
            @endif
        });
    </script>

</x-app-layout>
