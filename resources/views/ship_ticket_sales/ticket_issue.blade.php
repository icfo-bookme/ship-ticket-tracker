<x-app-layout>
    @php
        $paymentMethods = $sale->payments
            ->pluck('payment_method')
            ->filter()
            ->unique()
            ->implode(', ');
    @endphp

    <div class="flex justify-between items-center mt-1 ml-5">
        <h2 class="font-semibold text-base text-gray-800 leading-tight">
            <i class="fas fa-edit mr-2 text-blue-600"></i>
            Ship Ticket Sale #{{ $sale->id }}
        </h2>
        <a href="/sales/status/payment-verified"
            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-2.5 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5">
            <i class="fas fa-arrow-left mr-2"></i> Back to List
        </a>

    </div>

    @if ($ticketIssueViews->isNotEmpty())
        <div class="mx-5 mt-3 rounded border border-gray-200 bg-gray-50 p-3">
            <h3 class="text-sm font-semibold text-gray-800">Seen by</h3>
            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-gray-600">
                @foreach ($ticketIssueViews as $ticketIssueView)
                    <span>
                        {{ $ticketIssueView->user?->name ?? 'Unknown user' }}
                        <span class="text-gray-400">({{ $ticketIssueView->last_viewed_at->timezone('Asia/Dhaka')->format('d M Y, h:i A') }})</span>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

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

    <div class="py-8 {{ $hasRefundActivity ? 'bg-red-100 border-y-4 border-red-500' : '' }}">
        <div class=" mx-auto sm:px-6">
            @if ($hasRefundActivity)
                <div class="mb-4 rounded-lg border border-red-300 bg-red-500 p-3 font-semibold text-white">
                    <i class="fas fa-triangle-exclamation mr-2"></i>
                    Refund request or refunded ticket exists for this sale.
                </div>
            @endif
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
                    <form action="{{ route('ship-ticket-issue.update', $sale->id) }}" method="POST" class=""
                        id="ticketForm" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

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
                                    <input type="number" name="number_of_ticket" id="number_of_ticket" required
                                        min="1" value="{{ old('number_of_ticket', $sale->number_of_ticket) }}"
                                        class="copyable-field bg-green-500 w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
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
                                <div class="bg-white rounded-lg p-3 shadow-sm border border-blue-200">
                                    <h4 class="font-bold text-sm text-blue-800 mb-2 flex items-center">
                                        <i class="fas fa-ship mr-2 text-blue-600"></i>Departure Packages
                                    </h4>
                                    <div class="space-y-3">
                                        @foreach ($sale->categories->where('type', 'departure')->where('quantity', '>', 0) as $departureCategory)
                                            @php
                                                $package = $departureCategory->package;
                                                $departureQuantity = $departureCategory->quantity;
                                            @endphp
                                            <div class="grid grid-cols-2 items-center p-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out">
                                                <div class="flex items-center">
                                                    <input type="radio" name="departure_package" value="{{ $package->id }}" id="departure_package_{{ $package->id }}" {{ $departureCategory ? 'checked' : '' }} class="copyable-field focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300">
                                                    <label for="departure_package_{{ $package->id }}" class="ml-3 block text-sm font-medium text-gray-700">
                                                        <span class="font-semibold">{{ $package->name }}</span>
                                                        <span class="text-blue-600 font-bold ml-2">৳{{ number_format($package->price, 2) }}</span>
                                                    </label>
                                                </div>
                                                <div class="flex items-center justify-end space-x-2">
                                                    <label for="departure_quantity_{{ $package->id }}" class="text-sm font-semibold text-gray-700">Quantity:</label>
                                                    <input type="number" name="departure_quantity[{{ $package->id }}]" id="departure_quantity_{{ $package->id }}" value="{{ $departureQuantity }}" min="0" class="w-20 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5 text-center">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="bg-white rounded-lg p-3 shadow-sm border border-blue-200">
                                    <h4 class="font-bold text-sm text-blue-800 mb-2 flex items-center">
                                        <i class="fas fa-undo-alt mr-2 text-blue-600"></i>Return Packages
                                    </h4>
                                    <div class="space-y-3">
                                        @foreach ($sale->categories->where('type', 'return')->where('quantity', '>', 0) as $returnCategory)
                                            @php
                                                $package = $returnCategory->package;
                                                $returnQuantity = $returnCategory->quantity;
                                            @endphp
                                            <div class="grid grid-cols-2 items-center p-3 hover:bg-blue-50 rounded-lg transition duration-200 ease-in-out">
                                                <div class="flex items-center">
                                                    <input type="radio" name="return_package" value="{{ $package->id }}" id="return_package_{{ $package->id }}" {{ $returnCategory ? 'checked' : '' }} class="copyable-field focus:ring-blue-500 h-5 w-5 text-blue-600 border-gray-300">
                                                    <label for="return_package_{{ $package->id }}" class="ml-3 block text-sm font-medium text-gray-700">
                                                        <span class="font-semibold">{{ $package->name }}</span>
                                                        <span class="text-blue-600 font-bold ml-2">৳{{ number_format($package->round_trip_price - $package->price, 2) }}</span>
                                                    </label>
                                                </div>
                                                <div class="flex items-center justify-end space-x-2">
                                                    <label for="return_quantity_{{ $package->id }}" class="text-sm font-semibold text-gray-700">Quantity:</label>
                                                    <input type="number" name="return_quantity[{{ $package->id }}]" id="return_quantity_{{ $package->id }}" value="{{ $returnQuantity }}" min="0" class="w-20 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5 text-center">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($hasRefundActivity)
                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 shadow-sm">
                            <div class="mb-3 flex items-center">
                                <div class="mr-3 rounded-lg bg-amber-500 p-2">
                                    <i class="fas fa-rotate-left text-white text-sm"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-800">Refund Summary</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-left text-sm">
                                    <thead class="border-b border-amber-200 text-gray-700">
                                        <tr>
                                            <th class="px-3 py-2">Category</th>
                                            <th class="px-3 py-2">Type</th>
                                            <th class="px-3 py-2">Purchased</th>
                                            <th class="px-3 py-2">Refunded</th>
                                            <th class="px-3 py-2">Remaining</th>
                                            <th class="px-3 py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($refundSummary as $refundRow)
                                            <tr class="border-b border-amber-100 last:border-0">
                                                <td class="px-3 py-2 font-medium">{{ $refundRow['name'] }}</td>
                                                <td class="px-3 py-2">{{ $refundRow['type'] }}</td>
                                                <td class="px-3 py-2">{{ $refundRow['purchased'] }}</td>
                                                <td class="px-3 py-2">{{ $refundRow['refunded'] }}</td>
                                                <td class="px-3 py-2">{{ $refundRow['remaining'] }}</td>
                                                    <td class="px-3 py-2 font-semibold {{ $refundRow['status'] !== 'No Refund' ? 'text-red-700' : 'text-gray-700' }}">{{ $refundRow['status'] }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="px-3 py-3 text-gray-500">No ticket categories found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            </div>
                        @endif

                        @php
                            $extraReceivedAmount = (float) $sale->received_amount
                                - ((float) $sale->ticket_fee + (float) $sale->other_fee);
                        @endphp

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
                                        <input type="number" step="0.01" name="ticket_fee" id="ticket_fee"
                                            required value="{{ old('ticket_fee', $sale->ticket_fee) }}"
                                            class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5 text-sm font-bold text-gray-800">
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
                                    </div>
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
                                            class="copyable-field w-full border-blue-200 bg-blue-50 rounded-lg shadow-sm py-1.5 px-2.5 text-sm font-bold text-blue-700">
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

                                <div class="bg-white rounded-lg p-3 shadow-sm border border-indigo-200">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-sm font-semibold text-gray-700">Payment Methods</label>
                                    </div>
                                    <div class="text-sm font-bold text-indigo-700 break-words">
                                        {{ $paymentMethods ?: 'N/A' }}
                                    </div>
                                </div>

                                @if ($extraReceivedAmount > 0)
                                    <div class="bg-white rounded-lg p-3 shadow-sm border border-amber-200">
                                        <div class="flex items-center justify-between mb-1">
                                            <label for="extra_received_amount" class="block text-sm font-semibold text-gray-700">Extra Received Amount</label>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="text-gray-500 mr-2">৳</span>
                                            <input type="number" step="0.01" id="extra_received_amount" readonly
                                                value="{{ number_format($extraReceivedAmount, 2, '.', '') }}"
                                                class="w-full border-amber-200 bg-amber-50 rounded-lg shadow-sm py-1.5 px-2.5 text-sm font-bold text-amber-700">
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Payment Records Section -->
                            <div class="mt-3 hidden">
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

                                <div class="bg-white rounded-lg p-3 shadow-sm border border-red-200">
                                    <label class="block text-sm font-semibold text-gray-700">Extra Refunded Amount</label>
                                    <div class="mt-2 text-lg font-bold text-red-700">৳ {{ number_format((float) ($sale->extra_refunded_amount ?? 0), 2) }}</div>
                                </div>

                                <div class="bg-white rounded-lg p-3 shadow-sm border border-amber-200">
                                    <label class="block text-sm font-semibold text-gray-700">Extra Refund Pending</label>
                                    <div class="mt-2 text-lg font-bold text-amber-700">৳ {{ number_format((float) ($sale->extra_refund_pending_amount ?? 0), 2) }}</div>
                                </div>

                                <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-200">
                                    <label class="block text-sm font-semibold text-gray-700">Extra Available</label>
                                    <div class="mt-2 text-lg font-bold text-gray-700">৳ {{ number_format((float) ($sale->extra_remaining_amount ?? 0), 2) }}</div>
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
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Sales Information -->
                        <div class="hidden bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100">
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

                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label for="status"
                                            class="block text-sm font-semibold text-gray-700">Status</label>
                                        <button type="button"
                                            class="copy-field-btn text-blue-600 hover:text-blue-800 transition duration-200"
                                            data-field="status" title="Copy Status">
                                            <i class="fas fa-copy text-xs"></i>
                                        </button>
                                    </div>
                                    <select name="status" id="status"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
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

                        @if (filled($sale->remark1) || filled($sale->remark2))
                        <!-- Remarks -->
                        <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100">
                            <div class="flex items-center mb-2">
                                <div class="bg-blue-600 p-2 rounded-lg mr-3">
                                    <i class="fas fa-sticky-note text-white text-sm"></i>
                                </div>
                                <h3 class="text-base font-bold text-gray-800">Remarks</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @if (filled($sale->remark1))
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
                                @endif

                                @if (filled($sale->remark2))
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
                                @endif
                            </div>
                        </div>
                        @endif

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
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @if ($sale->status == 'payment-verified')
                            @php $count = $number + 1; @endphp
                            <div class="bg-blue-950 rounded-lg p-3">
                                <div id="pdf-fields"
                                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2  shadow-sm mt-3">

                                    @for ($i = 1; $i <= $totalDepartureTickets; $i += 5)
                                        <div class="pdf-item mb-2 border p-3 rounded-lg relative">
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
                                                value="{{ $pdfFilenamePrefix . '-' . $count }}"
                                                class="copyable-field w-full border-gray-300 rounded-lg py-1.5 px-2.5">
                                        </div>

                                        @php $count++; @endphp
                                    @endfor

                                </div>

                                <div class="">
                                    <button type="button" id="addPdfField" data-permission="sales.issue"
                                        class="mt-3 px-2.5 py-1.5 bg-blue-600 text-white rounded-lg rounded-lg hover:bg-blue-700">
                                        + Add New PDF Field
                                    </button>
                                </div>
                            </div>
                        @endif



                        <!-- PDF Section -->
                        <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100 mt-3">

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

                        <!-- ADD MORE PDF FIELDS SECTION -->
                        @if ($sale->status != 'pending' && $sale->status != 'payment-verified')
                            <div class="bg-yellow-50 rounded-lg p-3 shadow-sm border border-yellow-200 mt-3">
                                <div class="flex items-center mb-2">
                                    <div class="bg-yellow-600 p-2 rounded-lg mr-3">
                                        <i class="fas fa-file-pdf text-white text-sm"></i>
                                    </div>
                                    <h3 class="text-base font-bold text-gray-800">Add More PDF Fields</h3>
                                </div>

                                <div class="mb-3">
                                    <p class="text-gray-600 mb-2">Add more PDF filename fields. Format:
                                        {{ $pdfFilenamePrefix }}-{number}</p>

                                    @php $nextPdfNumber = $number + 1; @endphp

                                    <div id="additional-pdf-fields" class="space-y-2">
                                        <!-- Additional PDF fields will be added here -->
                                    </div>

                                    <button type="button" id="add-additional-pdf" data-permission="sales.issue"
                                        class="mt-2 bg-green-500 hover:bg-green-600 text-white font-bold py-1.5 px-2.5 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-md">
                                        <i class="fas fa-plus-circle mr-2"></i>Add PDF Field
                                    </button>
                                </div>
                            </div>
                        @endif


                        @if (($sale->status == 'payment-verified' && $number > 0) || $groupingMessage)
                            <div class="bg-yellow-50 rounded-lg p-3 shadow-sm border border-yellow-200 mt-3">
                                @if ($sale->status == 'payment-verified' && $number > 0)
                                    <div class="flex items-center mb-2">
                                        <div class="bg-yellow-600 p-2 rounded-lg mr-3">
                                            <i class="fas fa-exclamation-triangle text-white text-sm"></i>
                                        </div>

                                        <h3 class="text-base font-bold text-gray-800">
                                            Important Notice
                                        </h3>
                                    </div>

                                    <p class="text-gray-700 text-sm leading-relaxed">
                                        Tickets PDF document has already been generated using this WhatsApp number.
                                        Please review the existing document before requesting a new one.
                                    </p>

                                    <div class="mt-3 flex items-center gap-5 text-sm text-gray-700">
                                        <span class="font-semibold">Request a new PDF?</span>
                                        <label class="inline-flex items-center gap-2">
                                            <input type="radio" name="existing_pdf_action" value="yes">
                                            <span>Yes</span>
                                        </label>
                                        <label class="inline-flex items-center gap-2">
                                            <input type="radio" name="existing_pdf_action" value="no" checked>
                                            <span>No</span>
                                        </label>
                                    </div>

                                    <p id="existing-pdf-help" class="mt-2 text-sm text-red-700">
                                        Select Yes only after reviewing the existing PDF.
                                    </p>
                                @endif

                                @if ($groupingMessage)
                                    <p class="rounded border border-amber-300 bg-amber-100 px-3 py-2 text-sm text-amber-900 @if ($sale->status == 'payment-verified' && $number > 0) mt-3 @endif">
                                        {{ $groupingMessage }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if ($groupByStatus)
                            <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100 mt-3">
                                <p class="font-bold text-base">Do You Want to group tickets:</p>
                                <div class="mt-2 flex justify-around">
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
                            </div>
                        @endif

                        @if ($sale->status == 'shipped' || $sale->status == 'ticket-printed' || $sale->status == 'shipment_id_entered')
                            <!-- Shipment Info Section -->
                            <div class="bg-blue-50 rounded-lg p-3 shadow-sm border border-blue-100">
                                <div class="flex items-center mb-2">
                                    <div class="bg-red-600 p-2 rounded-lg mr-3">
                                        <i class="fas fa-truck text-white text-sm"></i>
                                    </div>
                                    <h3 class="text-base font-bold text-red-800">Add Shipment Info</h3>
                                </div>
                                <div>
                                    <input type="text" name="shipment_id"
                                        value="{{ $sale->shipment->shipment_id ?? '' }}"
                                        class="copyable-field w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition duration-200 ease-in-out py-1.5 px-2.5">
                                </div>
                            </div>
                        @endif

                        <!-- PDF and grouping are the only editable values on this page. -->
                        <div class="mt-3 flex justify-end gap-3">
                            <a href="/sales/status/pending"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-1.5 px-4 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-md">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>

                            <button type="submit" data-permission="sales.issue"
                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1.5 px-4 rounded-lg transition duration-200 ease-in-out transform hover:-translate-y-0.5 shadow-lg hover:shadow-xl">
                                <i class="fas fa-save mr-2"></i>{{ $nextSale ? 'Save & Next' : 'Save PDF & Grouping' }}
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



    

    


    <div id="ticketIssueConfig" data-whatsapp="{{ $pdfFilenamePrefix }}" data-current-pdf-number="{{ $number + 1 }}" data-pdf-index="{{ ($count ?? ($number ?? 1)) - 1 }}" data-payment-verified="{{ $sale->status === 'payment-verified' ? 'true' : 'false' }}"></div>

    @vite(['resources/js/pages/ticket-issue.js'])
</x-app-layout>
