@can('sales.view')
    @php
        $visibleStatuses = array_filter(config('sales.navbar_statuses', []), fn (string $icon, string $status): bool => auth()->user()->can('sales.status.'.$status), ARRAY_FILTER_USE_BOTH);
    @endphp
    @if ($visibleStatuses !== [])
        <div data-sale-status-counters data-url="{{ route('sales.status-counts') }}"
            class="flex items-center gap-1 md:gap-3" role="navigation" aria-label="Sales status counts">
            @foreach ($visibleStatuses as $status => $icon)
                @php
                    $label = config('sales.statuses.'.$status);
                    $active = request()->routeIs('sales.index', 'sales.data', 'ship-ticket-sales.index')
                        && (request()->route('status') ?? 'pending') === $status;
                    $path = match ($icon) {
                        'clock' => 'M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z',
                        'payment' => 'M12 6v12m3-9c0-1.5-1.5-2-3-2s-3 .5-3 2 1.5 2 3 3 3 1.5 3 3-1.5 2-3 2-3-.5-3-2M17 5l2 2 3-3M5 4a9 9 0 00-2 14 9 9 0 0015 0',
                        'ticket' => 'M3 7h18v3a2 2 0 000 4v3H3v-3a2 2 0 000-4V7zm12 1v2m0 2v2m0 2v1',
                        'printer' => 'M7 8V3h10v5M7 17H4V8h16v9h-3M7 14h10v7H7v-7zm10-3h1',
                        'box' => 'M3 7l9-4 9 4v10l-9 4-9-4V7zm0 0l9 4 9-4M12 11v10M7.5 5l9 4',
                    };
                @endphp
                <a href="{{ route('sales.index', $status) }}" data-status-counter="{{ $status }}" data-label="{{ $label }}"
                    aria-label="{{ $label }}" @if ($active) aria-current="page" @endif
                    class="group relative inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition focus:outline-none focus:ring-2 focus:ring-blue-300 sm:h-10 sm:w-10 {{ $active ? 'bg-white/10 text-blue-300' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-6 w-6 sm:h-7 sm:w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
                    </svg>
                    <span data-count-badge class="hidden absolute -right-1 -top-1 min-w-[18px] rounded-full border-2 border-[#003366] bg-red-600 px-1 text-center text-[10px] font-bold leading-4 text-white"></span>
                    <span data-count-tooltip role="tooltip" class="pointer-events-none absolute left-1/2 top-full z-[60] mt-2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100">{{ $label }}</span>
                </a>
            @endforeach
        </div>
        @vite(['resources/js/layout/sale-status-counters.js'])
    @endif
@endcan
