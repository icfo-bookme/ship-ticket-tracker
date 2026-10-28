<?php

namespace App\Services\Booking;

use App\Models\TicketBookingRequest;

class TicketBookingRequestService
{
    /**
     * @param  array{name: string, mobile: string, ticket_quantity: int|string, package_name: string, trip_type: string, journey_date: string, return_date?: string|null}  $data
     */
    public function create(array $data): TicketBookingRequest
    {
        return TicketBookingRequest::create([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'ticket_quantity' => (int) $data['ticket_quantity'],
            'package_name' => $data['package_name'],
            'trip_type' => $data['trip_type'],
            'journey_date' => $data['journey_date'],
            'return_date' => $data['trip_type'] === 'round_trip' ? ($data['return_date'] ?? null) : null,
            'status' => 'pending',
        ]);
    }
}
