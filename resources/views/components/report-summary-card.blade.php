@props(['id', 'label', 'emphasis' => false])

<div @class([
    'rounded-md border p-4 shadow-sm',
    'border-emerald-300 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-950/40' => $emphasis,
    'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' => ! $emphasis,
])>
    <p @class([
        'text-sm font-medium',
        'text-emerald-800 dark:text-emerald-200' => $emphasis,
        'text-gray-500 dark:text-gray-400' => ! $emphasis,
    ])>{{ $label }}</p>
    <p id="{{ $id }}" @class([
        'mt-1 text-xl font-semibold',
        'text-emerald-950 dark:text-emerald-100' => $emphasis,
        'text-gray-900 dark:text-gray-100' => ! $emphasis,
    ])>{{ str_contains($id, 'Tickets') || (str_contains($id, 'Bftn') && !str_contains($id, 'Amount')) ? '0' : '0.00' }}</p>
</div>
