<x-app-layout>
    <div class="mx-auto flex flex-col gap-5 px-3 py-6 sm:px-6 lg:px-8">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Audit Log</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Who created or updated each sale. Newest first.</p>
        </div>
        <div class="rounded-xl border border-gray-200  p-4 shadow-sm sm:p-6 dark:border-gray-700 dark:bg-gray-800">
            <x-data-table id="auditLogsTable"
                :headings="['Date & Time', 'Sale ID', 'User', 'Activity']"
                :url="route('audit-logs.index')" :ordering="false" :order="[]" :delegateActions="false"
                :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />
        </div>
    </div>
    @vite(['resources/js/pages/audit-logs.js'])
</x-app-layout>
