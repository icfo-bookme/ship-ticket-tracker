<x-app-layout>
    <div id="extraReceivedPage" data-refund-url="{{ route('extra-received.refund', ['id' => '__ID__']) }}" data-adjust-url="{{ route('extra-received.adjust', ['id' => '__ID__']) }}" class="py-6">
        @vite(['resources/js/pages/extra-received.js'])
        <div class=" mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Extra Received Sales</h2>
            </div>

            

            <x-data-table id="extraReceivedTable" :headings="[
                'Sale ID', 'Customer Name', 'Mobile', 'Ship', 'Journey Date',
                'Total Payable', 'Received Amount', 'Extra Received Amount', 'Action'
            ]" url="{{ route('extra-received.data') }}" :ordering="false" :delegateActions="false" :order="[]" />
        </div>
    </div>
</x-app-layout>


