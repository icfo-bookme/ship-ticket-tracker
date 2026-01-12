@php
    // Current status passed from controller, e.g., 'pending', 'payment-verified', etc.
    $currentStatus = request()->segment(3) ?? 'pending'; 
@endphp

<div class="">
    <div id="statusTabs" class="flex flex-wrap gap-2 sm:gap-3 mb-2 bg-gray-50 dark:bg-gray-800 p-2 sm:p-3 shadow-sm">
        @php
            $statuses = [
                'pending' => 'Pending',
                'payment-verified' => 'Payment Verified',
                'ticket-issued' => 'Ticket Issued',
                'ticket-printed' => 'Ticket Printed',
                'shipment_id_entered' => 'Shipment ID Entered',
                'shipped' => 'Shipped',
            ];
        @endphp

        @foreach ($statuses as $status => $label)
            <a href="{{ url('sales/status/'.$status) }}"
               class="status-tab px-4 sm:px-6 py-2.5 text-sm font-medium rounded-md transition-all duration-200
                      {{ $currentStatus === $status ? 'bg-blue-950 text-gray-300 shadow-sm' : 'bg-gray-200 text-gray-800 hover:text-blue-100 hover:bg-blue-700' }}
                      focus:outline-none focus:ring-2 focus:ring-blue-500">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>
