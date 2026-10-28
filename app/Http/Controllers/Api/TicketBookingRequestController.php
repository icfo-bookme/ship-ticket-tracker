<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTicketBookingRequest;
use App\Services\Booking\TicketBookingRequestService;
use Illuminate\Http\JsonResponse;

class TicketBookingRequestController extends Controller
{
    public function __construct(private readonly TicketBookingRequestService $bookings) {}

    public function store(StoreTicketBookingRequest $request): JsonResponse
    {
        $booking = $this->bookings->create($request->validated());

        return response()->json([
            'message' => 'Booking request submitted successfully.',
            'data' => [
                'id' => $booking->id,
                'status' => $booking->status,
            ],
        ], 201)->header('Cache-Control', 'no-store');
    }
}
