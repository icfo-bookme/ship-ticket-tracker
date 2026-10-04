<x-app-layout>
    <div id="packagesPage" data-base-url="{{ route('ship-packages.store') }}" data-record-url="{{ route('ship-packages.record', ['id' => '__ID__']) }}" class="py-6">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between pb-5">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">Ship Packages</h2>
                <button type="button" data-modal-target="package-modal" data-modal-toggle="package-modal" class="addBtn rounded bg-red-500 px-2 py-1 text-white">+ Add New Package</button>
            </div>
            <x-data-table id="packagesTable" :headings="['ID', 'Name', 'Price', 'Round Trip Price', 'Action']" url="{{ route('ship-packages.data', ['id' => $id]) }}" :delegateActions="false" />
        </div>
    </div>

    <x-entity-modal id="package-modal" title="Ship Package" formId="packageForm" submitText="Save" maxWidth="md">
        <form id="packageForm">
            <input type="hidden" name="ship_id" value="{{ $id }}">
            <div class="px-6 py-4">
                <div class="mb-4">
                    <label for="package-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Package Name</label>
                    <input type="text" name="name" id="package-name" required class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="mb-4">
                    <label for="package-price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Price</label>
                    <input type="number" name="price" id="package-price" required min="0" step="0.01" class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="mb-4">
                    <label for="package-round-trip-price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Round Trip Price</label>
                    <input type="number" name="round_trip_price" id="package-round-trip-price" required min="0" step="0.01" class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
        </form>
    </x-entity-modal>

    @vite(['resources/js/pages/ship-packages.js'])
</x-app-layout>
