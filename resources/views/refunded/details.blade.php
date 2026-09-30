<x-app-layout>
    @php
        $completedRefunds = $sale->refunds->where('status', 'completed');
        $totalRefundedTickets = $completedRefunds->sum('refunded_number_of_tickets');
        $totalGrossRefund = $completedRefunds->sum('gross_refund_amount');
        $totalDiscount = $completedRefunds->sum('refund_discount_amount');
        $totalDueAdjusted = $completedRefunds->sum('due_adjusted_amount');
        $totalFinalRefund = $completedRefunds->sum('customer_refund_amount');
    @endphp
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Refund Details</h1>
                <p class="text-sm text-gray-500">Sale #{{ $sale->id }} · {{ $sale->customer_name }}</p>
            </div>
            <a href="{{ url('/refunded') }}" class="rounded bg-gray-700 px-4 py-2 text-sm font-medium text-white">Back</a>
        </div>

        <section class="grid gap-4 md:grid-cols-2">
            <div class="rounded-lg bg-white p-5 shadow">
                <h2 class="mb-3 font-semibold text-gray-900">Customer Details</h2>
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-gray-500">Name</dt><dd>{{ $sale->customer_name }}</dd>
                    <dt class="text-gray-500">Mobile</dt><dd>{{ $sale->customer_mobile }}</dd>
                    <dt class="text-gray-500">WhatsApp</dt><dd>{{ $sale->whatsapp }}</dd>
                    <dt class="text-gray-500">Email</dt><dd>{{ $sale->email ?? 'N/A' }}</dd>
                </dl>
            </div>
            <div class="rounded-lg bg-white p-5 shadow">
                <h2 class="mb-3 font-semibold text-gray-900">Sale Details</h2>
                <dl class="grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-gray-500">Ship</dt><dd>{{ $sale->ships?->name ?? 'N/A' }}</dd>
                    <dt class="text-gray-500">Journey Date</dt><dd>{{ $sale->journey_date }}</dd>
                    <dt class="text-gray-500">Tickets</dt><dd>{{ $sale->number_of_ticket }}</dd>
                    <dt class="text-gray-500">Ticket Price</dt><dd>{{ number_format((float) $sale->ticket_fee, 2) }}</dd>
                    <dt class="text-gray-500">Other Fee</dt><dd>{{ number_format((float) $sale->other_fee, 2) }}</dd>
                    <dt class="text-gray-500">Current Due</dt><dd>{{ number_format((float) $sale->due_amount, 2) }}</dd>
                </dl>
            </div>
        </section>

        <section class="rounded-lg bg-white p-5 shadow">
            <h2 class="mb-4 font-semibold text-gray-900">Refund Summary</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ([
                    'Refunded Tickets' => $totalRefundedTickets,
                    'Gross Refund' => number_format($totalGrossRefund, 2),
                    'Due Adjusted' => number_format($totalDueAdjusted, 2),
                    'Final Customer Refund' => number_format($totalFinalRefund, 2),
                    'Refund Records' => $completedRefunds->count(),
                ] as $label => $value)
                    <div class="rounded bg-gray-50 p-3">
                        <p class="text-xs text-gray-500">{{ $label }}</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="overflow-x-auto rounded-lg bg-white p-5 shadow">
            <h2 class="mb-4 font-semibold text-gray-900">Completed Refund History</h2>
            <table class="min-w-full text-left text-sm">
                <thead><tr class="border-b text-gray-500"><th class="p-2">ID</th><th class="p-2">Tickets</th><th class="p-2">Gross</th><th class="p-2">Due Adjusted</th><th class="p-2">Final Refund</th><th class="p-2">Payment Details</th><th class="p-2">Completed</th></tr></thead>
                <tbody>
                    @foreach ($completedRefunds as $refund)
                        <tr class="border-b"><td class="p-2">{{ $refund->id }}</td><td class="p-2">{{ $refund->refunded_number_of_tickets }}</td><td class="p-2">{{ number_format((float) $refund->gross_refund_amount, 2) }}</td><td class="p-2">{{ number_format((float) $refund->due_adjusted_amount, 2) }}</td><td class="bg-red-100 p-2 font-semibold text-red-700">{{ number_format((float) $refund->customer_refund_amount, 2) }}</td><td class="max-w-xs whitespace-pre-wrap p-2">{{ $refund->refund_payment_details ?? 'N/A' }}</td><td class="p-2">{{ $refund->customer_refunded_at?->format('Y-m-d H:i') ?? 'N/A' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>
</x-app-layout>
