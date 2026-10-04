<x-app-layout>
    <div id="rolesPage" data-base-url="{{ route('roles.index') }}" data-super-admin-role="{{ config('roles.super_admin_role') }}" class="py-6">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between pb-5">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Roles Management</h2>
                <button type="button" data-modal-target="role-modal" data-modal-toggle="role-modal" class="addBtn rounded bg-blue-600 px-3 py-1.5 text-white">+ Add New Role</button>
            </div>
            <x-data-table id="rolesTable" :headings="['ID', 'Role Name', 'Action']" url="{{ route('roles.index') }}" :delegateActions="false" />
        </div>
    </div>

    <x-entity-modal id="role-modal" title="Role" formId="roleForm" submitText="Save" maxWidth="2xl">
        <form id="roleForm">
            <div class="px-6 py-4">
                <div class="mb-4">
                    <label for="role-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role Name</label>
                    <input type="text" name="name" id="role-name" required class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Permissions</label>
                    <p class="mb-2 text-xs text-gray-500">Select the permissions this role should have.</p>
                </div>
                <div class="mb-2 max-h-64 overflow-y-auto rounded-md border border-gray-200 p-3">
                    @foreach ($permissionGroups as $group => $perms)
                        <div class="mb-3">
                            <p class="mb-1 text-xs font-semibold uppercase text-gray-500">{{ $group }}</p>
                            <div class="grid grid-cols-2 gap-1">
                                @foreach ($perms as $perm)
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" class="role-permission rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="{{ $perm['name'] }}">
                                        <span>{{ $perm['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    @if ($permissionGroups->isEmpty())
                        <p class="text-sm text-gray-500">No permissions exist yet. Create permissions first.</p>
                    @endif
                </div>
            </div>
        </form>
    </x-entity-modal>

    @vite(['resources/js/pages/roles.js'])
</x-app-layout>
