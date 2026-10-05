<x-app-layout>
    <div id="shipsPage" data-base-url="{{ route('ships.index') }}" data-packages-url="{{ route('ship.packages', ['id' => '__ID__']) }}" class="py-6">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between pb-5">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Ships Details</h2>
                <button type="button" data-modal-target="ship-modal" class="addBtn rounded bg-red-500 px-2 py-1 text-white">+ Add New Ship</button>
            </div>
            <x-data-table id="shipsTable" :headings="['ID', 'Name', 'Route', 'Status', 'Action']" url="{{ route('ships.index') }}" :delegateActions="false" />
        </div>
    </div>

    <x-entity-modal id="ship-modal" title="Ship" formId="shipForm" submitText="Save" maxWidth="md">
        <form id="shipForm">
            <div class="px-6 py-4">
                <div class="mb-4">
                    <label for="ship-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ship Name</label>
                    <input type="text" name="name" id="ship-name" required class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="mb-4">
                    <label for="ship-route" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Route</label>
                    <input type="text" name="route" id="ship-route" required class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="mb-4">
                    <label for="ship-status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select name="status" id="ship-status" required class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
        </form>
    </x-entity-modal>

    @vite(['resources/js/pages/ships.js'])
</x-app-layout>
