<x-app-layout>
    <div id="permissionsPage" data-base-url="{{ route('permissions.index') }}" class="py-6">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between pb-5">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Permissions Management</h2>
                <button type="button" data-modal-target="permission-modal" class="addBtn rounded bg-blue-600 px-3 py-1.5 text-white">+ Add New Permission</button>
            </div>
            <x-data-table id="permissionsTable" :headings="['ID', 'Permission Name', 'Group', 'Assigned Roles', 'Action']" url="{{ route('permissions.index') }}" :delegateActions="false" />
        </div>
    </div>

    <x-entity-modal id="permission-modal" title="Permission" formId="permissionForm" submitText="Save" maxWidth="md">
        <form id="permissionForm">
            <div class="px-6 py-4">
                <label for="permission-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permission Name</label>
                <input type="text" name="name" id="permission-name" required placeholder="e.g. sales.create, reports.view" class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <p class="mt-2 text-xs text-gray-500">Use lowercase words separated by dots, such as <code>sales.create</code>.</p>
            </div>
        </form>
    </x-entity-modal>

    @vite(['resources/js/pages/permissions.js'])
</x-app-layout>
