@props(['id', 'label'])

<div class="rounded-md border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
    <p id="{{ $id }}" class="mt-1 text-xl font-semibold text-gray-900 dark:text-gray-100">{{ str_contains($id, 'Tickets') || (str_contains($id, 'Bftn') && !str_contains($id, 'Amount')) ? '0' : '0.00' }}</p>
</div>
