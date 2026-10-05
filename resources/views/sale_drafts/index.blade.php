<x-app-layout>
    <div id="saleDraftsPage" data-base-url="{{ route('sale-drafts.index') }}" class="w-full px-2 py-6 sm:px-4 lg:px-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Sale Drafts</h2>
                <p class="mt-1 text-sm text-gray-500">Save customer requirements for future ticket availability.</p>
            </div>
        </div>

        <div class="mb-6 rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-200">
            <form id="saleDraftForm" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="departureDate" class="block text-sm font-medium text-gray-700">Departure Date</label>
                    <input id="departureDate" name="departure_date" type="date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="returnDate" class="block text-sm font-medium text-gray-700">Return Date</label>
                    <input id="returnDate" name="return_date" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label for="draftShip" class="block text-sm font-medium text-gray-700">Ship</label>
                    <select id="draftShip" name="ship_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select a ship</option>
                        @foreach ($ships as $ship)
                            <option value="{{ $ship->id }}">{{ $ship->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-4 md:col-span-2">
                    <h3 class="text-sm font-semibold text-gray-800">Ticket Categories</h3>
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <section class="rounded-md border border-gray-200 p-4">
                            <h4 class="mb-3 text-sm font-medium text-gray-700">Departure</h4>
                            <div id="draftDepartureCategories" class="space-y-3"></div>
                            <p id="draftDepartureHint" class="text-sm text-gray-500">Select a ship to load categories.</p>
                        </section>
                        <section id="draftReturnSection" class="hidden rounded-md border border-gray-200 p-4">
                            <h4 class="mb-3 text-sm font-medium text-gray-700">Return</h4>
                            <div id="draftReturnCategories" class="space-y-3"></div>
                            <p id="draftReturnHint" class="text-sm text-gray-500">Select a ship to load categories.</p>
                        </section>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label for="draftDetails" class="block text-sm font-medium text-gray-700">Details</label>
                    <textarea id="draftDetails" name="details" rows="4" required placeholder="Paste the customer's ticket requirements here..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
                <div class="md:col-span-2">
                    <label for="draftNote" class="block text-sm font-medium text-gray-700">Note</label>
                    <textarea id="draftNote" name="note" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
                <div class="flex gap-3 md:col-span-2">
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"><span data-submit-label>Save Draft</span></button>
                    <button type="button" id="clearDraftButton" class="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">Clear</button>
                </div>
            </form>
        </div>

        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div>
                <label for="draftShipFilter" class="block text-sm font-medium text-gray-700">Filter by Ship</label>
                <select id="draftShipFilter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Ships</option>
                    @foreach ($ships as $ship)
                        <option value="{{ $ship->id }}">{{ $ship->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="draftCategoryFilter" class="block text-sm font-medium text-gray-700">Filter by Category</label>
                <select id="draftCategoryFilter" disabled class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select a ship first</option>
                </select>
            </div>
            <div>
                <label for="departureDateFilter" class="block text-sm font-medium text-gray-700">Filter by Departure Date</label>
                <input id="departureDateFilter" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="returnDateFilter" class="block text-sm font-medium text-gray-700">Filter by Return Date</label>
                <input id="returnDateFilter" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex items-end">
                <button type="button" id="clearDraftFilters" class="rounded-md bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">Clear Filters</button>
            </div>
        </div>

        <x-data-table id="saleDraftsTable" :headings="['ID', 'Departure Date', 'Return Date', 'Ship', 'Categories', 'Details', 'Note', 'Created At', 'Action']" url="{{ route('sale-drafts.index') }}" :ordering="false" :delegateActions="false" :order="[]" />
    </div>

    @vite(['resources/js/pages/sale-drafts.js'])
</x-app-layout>
