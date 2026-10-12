<x-app-layout>
    <div class="py-6" id="salesSourcesPage" data-base-url="{{ route('sales-sources.index') }}">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between pb-5">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Sales Sources</h2>
                @can('sales_sources.create')
                    <button type="button" data-modal-target="sales-source-modal"
                        class="rounded bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        + Add Sales Source
                    </button>
                @endcan
            </div>

            @can('sales_sources.view')
                <x-data-table id="salesSourcesTable" :headings="['ID', 'Name', 'Status', 'Sort Order', 'Action']"
                    url="{{ route('sales-sources.index') }}" :delegateActions="false" />
            @endcan
        </div>
    </div>

    <x-entity-modal id="sales-source-modal" title="Sales Source" formId="salesSourceForm" submitText="Save" maxWidth="md">
        <form id="salesSourceForm">
            <div class="space-y-4 px-6 py-4">
                <div>
                    <label for="salesSourceName" class="block text-sm font-medium text-gray-700">Name</label>
                    <input id="salesSourceName" type="text" name="name" required maxlength="255"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="salesSourceActive" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="salesSourceActive" name="is_active" required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div>
                    <label for="salesSourceSortOrder" class="block text-sm font-medium text-gray-700">Sort Order</label>
                    <input id="salesSourceSortOrder" type="number" name="sort_order" min="0" step="1" value="0"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
        </form>
    </x-entity-modal>

    @vite(['resources/js/pages/sales-sources.js'])
</x-app-layout>
