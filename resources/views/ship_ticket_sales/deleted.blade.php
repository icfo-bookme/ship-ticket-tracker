<x-app-layout>
    <div class="space-y-5">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Deleted Sales</h1>
            <p class="mt-1 text-sm text-gray-600">Restore a deleted sale to return it to active lists and reports.</p>
        </div>

        <x-data-table
            id="deletedSalesTable"
            :headings="['ID', 'Customer', 'Mobile', 'Ship', 'Company', 'Journey Date', 'Status', 'Deleted At', 'Action']"
            :url="route('ship-ticket-sales.deleted.data')"
            :ordering="false"
            :delegateActions="false"
            :order="[]"
        />
    </div>

    <div
        id="deletedSalesConfig"
        data-restore-url="{{ route('ship-ticket-sales.restore', ['id' => '__ID__']) }}"
    ></div>

    @vite(['resources/js/pages/deleted-sales.js'])
</x-app-layout>
