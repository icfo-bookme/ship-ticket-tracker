<x-app-layout>
    <div id="usersPage" data-base-url="{{ route('users.index') }}" class="py-6">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between pb-5">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Users Management</h2>
            <button type="button" data-modal-target="user-modal"
                    class="addBtn rounded bg-blue-600 px-3 py-1.5 text-white">+ Add New User</button>
            </div>
            <x-data-table id="usersTable" :headings="['ID', 'Name', 'Email', 'Role', 'Action']" url="{{ route('users.index') }}" :delegateActions="false" />
        </div>
    </div>

    <x-entity-modal id="user-modal" title="User" formId="userForm" submitText="Save" maxWidth="lg">
        <form id="userForm">
            <div class="grid grid-cols-1 gap-2 px-6 py-4 md:grid-cols-2">
                <div class="mb-4">
                    <label for="user-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full
                        Name</label>
                    <input type="text" name="name" id="user-name" required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="mb-4">
                    <label for="user-email"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" name="email" id="user-email" required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="mb-4">
                    <label for="user-password"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                    <input type="password" name="password" id="user-password" required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500">Leave blank during update to keep the current password.</p>
                </div>
                <div class="mb-4" data-password-confirmation>
                    <label for="user-password-confirmation"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="user-password-confirmation" required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="mb-4">
                    <label for="user-role"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                    <select name="role" id="user-role" required
                        class="mt-1 block w-full rounded-md border border-gray-300 px-4 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">-- Select Role --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </x-entity-modal>

    @vite(['resources/js/pages/users.js'])
</x-app-layout>
