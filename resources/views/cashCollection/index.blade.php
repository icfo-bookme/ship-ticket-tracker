<x-app-layout>
    <div id="cashCollectionsPage" data-base-url="{{ route('cash-collections.index') }}" data-available-cash="{{ number_format($availableCashAmount, 2, '.', '') }}" class="py-6">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between pb-5">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Cash Collection Details</h2>
                <button type="button" data-permission="cash_collections.create" data-modal-target="cash-collection-modal" class="addBtn rounded bg-red-500 px-2 py-1 text-white">+ Add New Cash Collection</button>
            </div>
            @can('cash_collections.view')
                <x-data-table id="cashCollectionsTable" :headings="['ID', 'Cashout Amount', 'Reason', 'Created Date', 'Updated Date', 'Action']" url="{{ route('cash-collections.index') }}" :delegateActions="false" />
            @endcan
        </div>
    </div>

    <x-entity-modal id="cash-collection-modal" title="Cash Collection" formId="cashCollectionForm" submitText="Save" maxWidth="md">
        <form id="cashCollectionForm">
            <div class="px-6 py-4">
                <div class="mb-4">
                    <label for="available-cash-amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Available Cash Amount</label>
                    <input type="text" name="available_cash_amount" id="available-cash-amount" value="{{ number_format($availableCashAmount, 2) }}" readonly class="mt-1 block w-full rounded-md border border-gray-300 bg-gray-100 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="mb-4">
                    <label for="cashout-amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cashout Amount</label>
                    <input type="number" name="cashout_amount" id="cashout-amount" required min="0" step="0.01" class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="mb-4">
                    <label for="cash-collection-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Reason For</label>
                    <input type="text" name="name" id="cash-collection-name" required class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
        </form>
    </x-entity-modal>

    @vite(['resources/js/pages/cash-collections.js'])
</x-app-layout>
