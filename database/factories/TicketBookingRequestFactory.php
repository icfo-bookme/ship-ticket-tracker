<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketBookingRequest>
 */
class TicketBookingRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'mobile' => fake()->numerify('017########'),
            'ticket_quantity' => fake()->numberBetween(1, 5),
            'package_name' => 'Lavender (01 Person)',
            'trip_type' => 'single',
            'journey_date' => now()->addWeek()->toDateString(),
            'return_date' => null,
            'status' => 'pending',
        ];
    }
}
