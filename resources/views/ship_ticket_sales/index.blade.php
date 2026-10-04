<x-app-layout>
    @include('layouts.tab')
    @include('ship_ticket_sales.sales')    
    @include('ship_ticket_sales.paymentDueModal')
    @vite(['resources/js/components/due-payment.js'])
</x-app-layout>
