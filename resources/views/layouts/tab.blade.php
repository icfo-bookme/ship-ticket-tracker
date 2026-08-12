@php
    // Status is passed by the controller; fall back to 'pending' for the index route.
    $currentStatus = $status ?? 'pending';
    $statuses = config('sales.tabs', []);
@endphp

<div class="">
    <div id="statusTabs" class="flex flex-wrap gap-2 sm:gap-3 mb-2 bg-gray-50 p-2 sm:p-3 shadow-sm">
        @foreach ($statuses as $statusKey)
            <a href="{{ route('sales.index', $statusKey) }}"
               class="status-tab px-4 sm:px-6 py-2.5 text-sm font-medium rounded-md transition-all duration-200
                      {{ $currentStatus === $statusKey ? 'bg-blue-950 text-gray-300 shadow-sm' : 'bg-gray-200 text-gray-800 hover:text-blue-100 hover:bg-blue-700' }}
                      focus:outline-none focus:ring-2 focus:ring-blue-500">
                {{ config('sales.statuses.' . $statusKey) }}
            </a>
        @endforeach
    </div>
</div>