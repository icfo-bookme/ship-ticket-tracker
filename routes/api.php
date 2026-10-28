<?php

use App\Http\Controllers\Api\TicketBookingRequestController;
use Illuminate\Support\Facades\Route;

Route::post('v1/ticket-booking-requests', [TicketBookingRequestController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('api.v1.ticket-booking-requests.store');
