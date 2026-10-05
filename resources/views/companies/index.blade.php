<x-app-layout>
<div id="companiesPage" data-base-url="{{ route('companies.index') }}" class="py-6">
    <div class="mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between pb-5">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Companies Details</h2>
            <button type="button" data-modal-target="company-modal"
                class="addBtn rounded bg-red-500 px-2 py-1 text-white">
                + Add New Company
            </button>
        </div>
        <x-data-table id="companiesTable" :headings="['ID', 'Name', 'Status', 'Action']" url="{{ route('companies.index') }}" :delegateActions="false" />
    </div>
</div>

<x-entity-modal id="company-modal" title="Company" formId="companyForm" submitText="Save" maxWidth="md">
    <form id="companyForm">
        <div class="px-6 py-4">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" name="name" id="name" required
                    class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select name="status" id="status" required
                    class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>
    </form>
</x-entity-modal>

@vite(['resources/js/pages/companies.js'])
</x-app-layout>
