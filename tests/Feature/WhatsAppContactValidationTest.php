<?php

use App\Http\Requests\Sales\StorePublicShipTicketSaleRequest;
use App\Http\Requests\Sales\StoreShipTicketSaleRequest;
use App\Http\Requests\Sales\UpdateShipTicketSaleRequest;
use Illuminate\Support\Facades\Validator;

it('requires either a WhatsApp number or username for ticket sale contact details', function (string $requestClass) {
    $request = new $requestClass;
    $rules = array_intersect_key($request->rules(), array_flip(['whatsapp', 'whatsapp_username']));

    expect(Validator::make([], $rules)->fails())->toBeTrue()
        ->and(Validator::make(['whatsapp' => '01712345678'], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['whatsapp_username' => 'customer_name'], $rules)->passes())->toBeTrue()
        ->and(Validator::make([
            'whatsapp' => '01712345678',
            'whatsapp_username' => 'customer_name',
        ], $rules)->passes())->toBeTrue();
})->with([
    'admin create' => StoreShipTicketSaleRequest::class,
    'public create' => StorePublicShipTicketSaleRequest::class,
    'sale update' => UpdateShipTicketSaleRequest::class,
]);
