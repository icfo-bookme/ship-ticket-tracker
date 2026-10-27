<div class="flex flex-col gap-5">
    @forelse ($logs as $log)
        <article class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        {{ match (class_basename($log->auditable_type)) {
                            'ShipTicketSale' => 'Sale',
                            'Payment' => 'Payment',
                            'Refund' => 'Refund',
                            default => \Illuminate\Support\Str::headline(class_basename($log->auditable_type)),
                        } }} · {{ \Illuminate\Support\Str::headline($log->event) }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $log->user_name ?? 'System / public booking' }}</p>
                    @if ($showSale ?? false)
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Sale #{{ $log->context['sale_id'] ?? $log->auditable_id }} · Record #{{ $log->auditable_id }}
                        </p>
                    @endif
                </div>
                <time datetime="{{ $log->created_at->toIso8601String() }}" class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $log->created_at->timezone(config('audit.timezone'))->format('d M Y, h:i A T') }}
                </time>
            </div>
            @if ($log->description)
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $log->description }}</p>
            @endif
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-700 dark:text-gray-200">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                        <tr><th scope="col" class="p-2">Field</th><th scope="col" class="p-2">Before</th><th scope="col" class="p-2">After</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach (array_unique([...array_keys($log->old_values ?? []), ...array_keys($log->new_values ?? [])]) as $field)
                            <tr class="align-top">
                                <th scope="row" class="w-1/4 p-2 font-medium">{{ \Illuminate\Support\Str::headline(preg_replace('/_id$/', '', $field)) }}</th>
                                <td class="p-2 break-words"><x-audit-value :value="$log->old_values[$field] ?? null" :field="$field" /></td>
                                <td class="p-2 break-words"><x-audit-value :value="$log->new_values[$field] ?? null" :field="$field" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    @empty
        <p class="py-4 text-sm text-gray-500 dark:text-gray-400">No history recorded yet. New activity will appear here.</p>
    @endforelse
</div>
@if ($logs->hasPages())
    <nav aria-label="Edit history pages" class="mt-5 flex items-center justify-between gap-3 text-sm">
        @if ($logs->previousPageUrl())
            <a data-audit-page href="{{ $logs->previousPageUrl() }}" class="rounded-lg border border-gray-300 px-3 py-2 text-blue-700 dark:border-gray-600 dark:text-blue-300">Previous</a>
        @else
            <span></span>
        @endif
        <span class="text-gray-500 dark:text-gray-400">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>
        @if ($logs->nextPageUrl())
            <a data-audit-page href="{{ $logs->nextPageUrl() }}" class="rounded-lg border border-gray-300 px-3 py-2 text-blue-700 dark:border-gray-600 dark:text-blue-300">Next</a>
        @else
            <span></span>
        @endif
    </nav>
@endif
