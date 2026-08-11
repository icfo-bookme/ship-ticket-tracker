<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Dashboard Analytics</h2>
                <p class="text-gray-600 text-sm mt-1">Comprehensive overview of ticket sales and distributions</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-sm text-gray-500 bg-white px-4 py-2 rounded-lg shadow-sm">
                    <span class="font-semibold">{{ \Carbon\Carbon::now()->format('l') }}</span>,
                    {{ \Carbon\Carbon::now()->format('F j, Y') }}
                </div>
                <button
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Export Report
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Status Cards Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6">
                @php
                    $statusCards = [
                        ['title' => 'Pending', 'count' => $pendingTickets, 'color' => 'yellow', 'icon' => '⏳'],
                        ['title' => 'Payment Verified', 'count' => $paymentVerified, 'color' => 'blue', 'icon' => '✓'],
                        ['title' => 'Ticket Issued', 'count' => $ticketIssued, 'color' => 'indigo', 'icon' => '🎫'],
                        ['title' => 'Ticket Printed', 'count' => $ticketPrinted, 'color' => 'purple', 'icon' => '🖨️'],
                        ['title' => 'Parcel Created', 'count' => $parcelsCreated, 'color' => 'green', 'icon' => '📦'],
                        ['title' => 'Shipped', 'count' => $shipped, 'color' => 'emerald', 'icon' => '🚚'],
                    ];
                    $maxStatusCount = max(array_column($statusCards, 'count'));
                @endphp

                @foreach ($statusCards as $card)
                    <div
                        class="bg-white rounded-xl shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300 overflow-hidden transform hover:-translate-y-1">
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-2xl">{{ $card['icon'] }}</span>
                                <span
                                    class="text-xs font-bold px-3 py-1 rounded-full bg-{{ $card['color'] }}-100 text-{{ $card['color'] }}-800 border border-{{ $card['color'] }}-200">
                                    {{ $card['title'] }}
                                </span>
                            </div>
                            <p class="text-3xl font-bold text-gray-900 mb-2">{{ $card['count'] }}</p>
                            <div class="h-2 w-full bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-{{ $card['color'] }}-500 to-{{ $card['color'] }}-600 rounded-full transition-all duration-500"
                                    style="width: {{ $maxStatusCount > 0 ? min(($card['count'] / $maxStatusCount) * 100, 100) : 0 }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ENHANCED: Advanced Distribution Analytics with Charts --}}
            <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl shadow-2xl p-8 border border-gray-100">
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">📈 Advanced Distribution Analytics</h2>
                            <p class="text-gray-600">Visual insights into ticket distribution patterns</p>
                        </div>
                        
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-blue-700">
                                {{ $shipTicketCounts->sum('ship_ticket_sales_count') }}</div>
                            <div class="text-sm text-blue-600 font-medium">Total Ship Tickets</div>
                            <div class="text-xs text-blue-500 mt-1">{{ $shipTicketCounts->count() }} active ships
                            </div>
                        </div>
                        <div class="bg-purple-50 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-purple-700">
                                {{ $companyTicketCounts->sum('ship_ticket_sales_count') }}</div>
                            <div class="text-sm text-purple-600 font-medium">Total Company Tickets</div>
                            <div class="text-xs text-purple-500 mt-1">{{ $companyTicketCounts->count() }} companies
                            </div>
                        </div>
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-gray-800">
                                {{ $shipTicketCounts->sum('ship_ticket_sales_count') + $companyTicketCounts->sum('ship_ticket_sales_count') }}
                            </div>
                            <div class="text-sm text-gray-700 font-medium">Combined Total</div>
                            <div class="text-xs text-gray-600 mt-1">All distribution tickets</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {{-- Ship Distribution Chart --}}
                    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                        <div class="bg-[#003366] px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-white">Ship Performance</h3>
                                    <p class="text-blue-100 text-sm">Ticket distribution by ship</p>
                                </div>
                                <svg class="w-8 h-8 text-white opacity-80" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="p-6">
                            {{-- Bar Chart Visualization --}}
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-semibold text-gray-900">Ticket Volume</h4>
                                    <span class="text-sm text-blue-600 font-medium">
                                        Top {{ min(5, $shipTicketCounts->count()) }} Ships
                                    </span>
                                </div>
                                <div class="space-y-4">
                                    @foreach ($shipTicketCounts->take(5) as $index => $ship)
                                        @php
                                            $maxCount = $shipTicketCounts->max('ship_ticket_sales_count');
                                            $percentage =
                                                $maxCount > 0 ? ($ship->ship_ticket_sales_count / $maxCount) * 100 : 0;
                                        @endphp
                                        <div>
                                            <div class="flex justify-between text-sm mb-1">
                                                <span class="font-medium text-gray-700 truncate">
                                                    {{ $ship->name ?? 'Unknown Ship' }}
                                                </span>
                                                <span
                                                    class="font-bold text-blue-700">{{ $ship->ship_ticket_sales_count }}</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-3">
                                                <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-3 rounded-full transition-all duration-1000 ease-out"
                                                    style="width: {{ $percentage }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Ship List with Analytics --}}
                            <div class="border-t pt-6">
                                <h4 class="font-semibold text-gray-900 mb-4">Ship Analytics</h4>
                                <div class="space-y-3 max-h-60 overflow-y-auto pr-2">
                                    @foreach ($shipTicketCounts as $index => $ship)
                                        <div
                                            class="flex items-center justify-between p-3 hover:bg-blue-50 rounded-lg transition-colors">
                                            <div class="flex items-center space-x-3">
                                                <div
                                                    class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                                    <span class="text-blue-700 font-bold">{{ $index + 1 }}</span>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-medium text-gray-900 truncate">
                                                        {{ $ship->name ?? 'Unknown Ship' }}</p>
                                                    <div class="flex items-center text-xs text-gray-500">
                                                        <span
                                                            class="inline-block w-2 h-2 rounded-full bg-blue-500 mr-1"></span>
                                                        {{ $ship->ship_ticket_sales_count }} tickets •
                                                        {{ $maxCount > 0 ? number_format(($ship->ship_ticket_sales_count / $maxCount) * 100, 1) : 0 }}%
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-lg font-bold text-blue-700">
                                                    {{ $ship->ship_ticket_sales_count }}</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Company Distribution Chart --}}
                    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-700 to-purple-900 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-white">Company Performance</h3>
                                    <p class="text-purple-100 text-sm">Ticket distribution by company</p>
                                </div>
                                <svg class="w-8 h-8 text-white opacity-80" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="p-6">
                            {{-- Donut/Pie Chart Visualization --}}
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-semibold text-gray-900">Market Share</h4>
                                    <span class="text-sm text-purple-600 font-medium">
                                        Top {{ min(5, $companyTicketCounts->count()) }} Companies
                                    </span>
                                </div>
                                <div class="space-y-4">
                                    @foreach ($companyTicketCounts->take(5) as $index => $company)
                                        @php
                                            $totalCompanies = $companyTicketCounts->sum('ship_ticket_sales_count');
                                            $percentage =
                                                $totalCompanies > 0
                                                    ? ($company->ship_ticket_sales_count / $totalCompanies) * 100
                                                    : 0;
                                            $colors = [
                                                'from-purple-400 to-purple-500',
                                                'from-indigo-400 to-indigo-500',
                                                'from-pink-400 to-pink-500',
                                                'from-rose-400 to-rose-500',
                                                'from-fuchsia-400 to-fuchsia-500',
                                            ];
                                            $colorClass = $colors[$index % count($colors)];
                                        @endphp
                                        <div class="flex items-center">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-gradient-to-r {{ $colorClass }} flex items-center justify-center mr-3">
                                                <span class="text-white text-xs font-bold">{{ $index + 1 }}</span>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex justify-between text-sm mb-1">
                                                    <span class="font-medium text-gray-700 truncate">
                                                        {{ $company->name ?? 'Unknown Company' }}
                                                    </span>
                                                    <span
                                                        class="font-bold text-purple-700">{{ number_format($percentage, 1) }}%</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="bg-gradient-to-r {{ $colorClass }} h-2 rounded-full transition-all duration-1000 ease-out"
                                                        style="width: {{ $percentage }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Company List with Analytics --}}
                            <div class="border-t pt-6">
                                <h4 class="font-semibold text-gray-900 mb-4">Company Analytics</h4>
                                <div class="space-y-3 max-h-60 overflow-y-auto pr-2">
                                    @foreach ($companyTicketCounts as $index => $company)
                                        @php
                                            $totalCompanies = $companyTicketCounts->sum('ship_ticket_sales_count');
                                            $percentage =
                                                $totalCompanies > 0
                                                    ? ($company->ship_ticket_sales_count / $totalCompanies) * 100
                                                    : 0;
                                        @endphp
                                        <div
                                            class="flex items-center justify-between p-3 hover:bg-purple-50 rounded-lg transition-colors">
                                            <div class="flex items-center space-x-3">
                                                <div
                                                    class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                                    <span class="text-purple-700 font-bold">{{ $index + 1 }}</span>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-medium text-gray-900 truncate">
                                                        {{ $company->name ?? 'Unknown Company' }}</p>
                                                    <div class="flex items-center text-xs text-gray-500">
                                                        <span
                                                            class="inline-block w-2 h-2 rounded-full bg-purple-500 mr-1"></span>
                                                        {{ $company->ship_ticket_sales_count }} tickets •
                                                        {{ number_format($percentage, 1) }}% share
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-lg font-bold text-purple-700">
                                                    {{ $company->ship_ticket_sales_count }}</div>
                                                <div class="text-xs text-gray-500">
                                                    {{ number_format($percentage, 1) }}%</div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Combined Comparison Chart --}}
                <div class="mt-8 bg-white rounded-2xl shadow-xl border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Performance Comparison</h3>
                            <p class="text-gray-600 text-sm">Ship vs Company distribution patterns</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
                                <span class="text-sm text-gray-600">Ships</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-purple-500 mr-2"></div>
                                <span class="text-sm text-gray-600">Companies</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        @php
                            $maxItems = max($shipTicketCounts->count(), $companyTicketCounts->count());
                            $maxCount = max(
                                $shipTicketCounts->max('ship_ticket_sales_count'),
                                $companyTicketCounts->max('ship_ticket_sales_count'),
                            );
                        @endphp

                        @for ($i = 0; $i < min(5, $maxItems); $i++)
                            @php
                                $ship = $shipTicketCounts[$i] ?? null;
                                $company = $companyTicketCounts[$i] ?? null;
                                $shipPercent =
                                    $ship && $maxCount > 0 ? ($ship->ship_ticket_sales_count / $maxCount) * 100 : 0;
                                $companyPercent =
                                    $company && $maxCount > 0
                                        ? ($company->ship_ticket_sales_count / $maxCount) * 100
                                        : 0;
                            @endphp
                            <div class="flex items-center">
                                <div class="w-32 text-sm text-gray-600 font-medium truncate mr-4">
                                    {{ $ship->name ?? 'N/A' }}
                                </div>
                                <div class="flex-1 flex items-center space-x-4">
                                    <div class="flex-1">
                                        <div class="h-6 bg-gradient-to-r from-blue-400 to-blue-600 rounded-lg transition-all duration-1000 ease-out"
                                            style="width: {{ $shipPercent }}%"></div>
                                        <div class="text-xs text-blue-600 mt-1 text-right pr-2">
                                            {{ $ship ? $ship->ship_ticket_sales_count : 0 }}
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="h-6 bg-gradient-to-r from-purple-400 to-purple-600 rounded-lg transition-all duration-1000 ease-out"
                                            style="width: {{ $companyPercent }}%"></div>
                                        <div class="text-xs text-purple-600 mt-1 text-right pr-2">
                                            {{ $company ? $company->ship_ticket_sales_count : 0 }}
                                        </div>
                                    </div>
                                </div>
                                <div class="w-32 text-sm text-gray-600 font-medium truncate ml-4 text-right">
                                    {{ $company->name ?? 'N/A' }}
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- Recent Tickets Table --}}
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Recent Ticket Transactions</h3>
                            <p class="text-gray-600 text-sm mt-1">Latest activities in the system</p>
                        </div>
                        <div class="flex space-x-3">
                            <button
                                class="inline-flex items-center px-4 py-2 text-gray-700 bg-gray-100 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                Filter
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                                    </path>
                                </svg>
                            </button>
                            {{-- <a href="{{ route('tickets.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                View All Tickets
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </a> --}}
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Ticket Details</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Customer Info</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Journey</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Amount</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($recentTickets as $ticket)
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-3">
                                            <div
                                                class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                                <span class="text-blue-700 font-bold">#{{ $ticket->id }}</span>
                                            </div>
                                            <div>
                                                <div class="text-sm text-gray-500">Ticket ID</div>
                                                <div class="font-semibold text-gray-900">#{{ $ticket->id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $ticket->customer_name }}</div>
                                            <div class="text-sm text-gray-500">{{ $ticket->customer_mobile }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="font-medium text-gray-900">
                                                {{ \Carbon\Carbon::parse($ticket->journey_date)->format('d M, Y') }}
                                            </div>
                                            <div class="text-sm text-gray-500">{{ $ticket->ships->name ?? 'N/A' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900">
                                            ৳{{ number_format($ticket->ticket_fee, 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $statusColors = [
                                                'pending' => 'yellow',
                                                'payment_verified' => 'blue',
                                                'ticket_issued' => 'indigo',
                                                'ticket_printed' => 'purple',
                                                'parcel_created' => 'green',
                                                'shipped' => 'emerald',
                                            ];
                                            $color = $statusColors[$ticket->status] ?? 'gray';
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-bold bg-{{ $color }}-100 text-{{ $color }}-800 border border-{{ $color }}-200">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            {{-- <a href="{{ route('tickets.show', $ticket->id) }}" 
                                               class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                                View
                                                <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                                </svg>
                                            </a> --}}
                                            <button
                                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                                Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Add Chart.js for interactive charts (optional) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // You can add interactive chart functionality here if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Animation for progress bars
            const progressBars = document.querySelectorAll('.transition-all');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
    </script>
</x-app-layout>
