<x-app-layout>
    <div id="whatsappPage" data-base-url="{{ route('whatsapp.index') }}" class="py-6">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between pb-5">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">WhatsApp Details</h2>
                <button type="button" data-modal-target="whatsapp-modal" class="addBtn rounded bg-blue-600 px-3 py-1.5 text-white">+ Add WhatsApp Details</button>
            </div>
            <x-data-table id="whatsappTable" :headings="['ID', 'Tag', 'WhatsApp Number', 'Form No', 'Form URL', 'Action']" url="{{ route('whatsapp.index') }}" :order="[]" :delegateActions="false" :lengthMenu="[[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]]" />
        </div>
    </div>

    <x-entity-modal id="whatsapp-modal" title="WhatsApp Details" formId="whatsappForm" submitText="Save" maxWidth="md">
        <form id="whatsappForm">
            <div class="space-y-4 px-6 py-4">
                <div>
                    <label for="whatsapp-tag" class="block text-sm font-medium text-gray-700">Tag</label>
                    <input type="text" name="tag" id="whatsapp-tag" required class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2">
                </div>
                <div>
                    <label for="whatsapp-number" class="block text-sm font-medium text-gray-700">WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" id="whatsapp-number" required class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2" placeholder="8801XXXXXXXXX">
                </div>
                <div>
                    <label for="whatsapp-form-no" class="block text-sm font-medium text-gray-700">Form No</label>
                    <input type="text" name="form_no" id="whatsapp-form-no" required class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2">
                </div>
                <div>
                    <label for="whatsapp-url" class="block text-sm font-medium text-gray-700">Form URL</label>
                    <input type="url" name="url" id="whatsapp-url" required class="mt-1 w-full rounded-md border border-gray-300 px-4 py-2">
                </div>
            </div>
        </form>
    </x-entity-modal>

    @vite(['resources/js/pages/whatsapp.js'])
</x-app-layout>
