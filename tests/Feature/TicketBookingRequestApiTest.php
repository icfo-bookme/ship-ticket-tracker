<?php

use App\Models\TicketBookingRequest;

beforeEach(function () {
    config(['services.ticket_booking.api_token' => 'booking-test-token']);
});

it('accepts an external booking without a session or csrf token', function () {
    $payload = TicketBookingRequest::factory()->raw();
    unset($payload['status']);

    $this->withToken('booking-test-token')
        ->postJson('/api/v1/ticket-booking-requests', $payload)
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('ticket_booking_requests', [
        'mobile' => $payload['mobile'],
        'package_name' => $payload['package_name'],
        'return_date' => null,
        'status' => 'pending',
    ]);
});

it('rejects missing invalid and unconfigured tokens', function () {
    $this->postJson('/api/v1/ticket-booking-requests', [])->assertForbidden();
    $this->withToken('wrong')->postJson('/api/v1/ticket-booking-requests', [])->assertForbidden();
    config(['services.ticket_booking.api_token' => null]);
    $this->withToken('booking-test-token')->postJson('/api/v1/ticket-booking-requests', [])->assertForbidden();
    $this->assertDatabaseCount('ticket_booking_requests', 0);
});

it('validates quantities dates and client supplied status', function () {
    $payload = TicketBookingRequest::factory()->raw([
        'ticket_quantity' => 0,
        'trip_type' => 'round_trip',
        'return_date' => null,
        'status' => 'confirmed',
    ]);

    $this->withToken('booking-test-token')->postJson('/api/v1/ticket-booking-requests', $payload)
        ->assertUnprocessable()->assertJsonValidationErrors(['ticket_quantity', 'return_date', 'status']);

    unset($payload['status']);
    $payload['ticket_quantity'] = 2;
    $payload['return_date'] = now()->toDateString();
    $this->withToken('booking-test-token')->postJson('/api/v1/ticket-booking-requests', $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('return_date');
    $this->assertDatabaseCount('ticket_booking_requests', 0);
});

it('saves round trip dates and returns json even without an accept header', function () {
    $payload = TicketBookingRequest::factory()->raw([
        'trip_type' => 'round_trip',
        'return_date' => now()->addWeeks(2)->toDateString(),
    ]);
    unset($payload['status']);

    $this->withToken('booking-test-token')->postJson('/api/v1/ticket-booking-requests', $payload)->assertCreated();
    expect(TicketBookingRequest::firstOrFail()->return_date->toDateString())->toBe($payload['return_date']);

    $this->post('/api/v1/ticket-booking-requests', [])->assertUnprocessable()->assertJsonValidationErrors('name');
});
