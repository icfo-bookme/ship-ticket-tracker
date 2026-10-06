<x-app-layout>
    <div id="excelSettingsPage" data-base-url="{{ route('excel-settings.index') }}" class="py-6">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between pb-5">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">Excel Setting</h2>
                <button type="button" data-permission="excel.create" data-modal-target="excel-setting-modal" class="addBtn rounded bg-blue-600 px-3 py-1.5 text-white">+ Add Excel Setting</button>
            </div>
            @can('excel.view')
                <x-data-table id="excelTable" :headings="['ID', 'Spreadsheet ID', 'Range', 'Action']" url="{{ route('excel-settings.index') }}" :delegateActions="false" />
            @endcan
        </div>
    </div>

    <x-entity-modal id="excel-setting-modal" title="Excel Setting" formId="excelSettingForm" submitText="Save" maxWidth="lg">
        <form id="excelSettingForm">
            <div class="px-6 py-4">
                <div class="mb-4">
                    <label for="spreadsheet-id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Spreadsheet ID</label>
                    <input type="text" name="spreadsheetId" id="spreadsheet-id" required class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="mb-4">
                    <label for="spreadsheet-range" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Range</label>
                    <input type="text" name="range" id="spreadsheet-range" required class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
        </form>
    </x-entity-modal>

    @vite(['resources/js/pages/excel-settings.js'])
</x-app-layout>
