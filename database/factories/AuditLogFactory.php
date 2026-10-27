<?php

namespace Database\Factories;

use App\Models\Ship;
use App\Models\ShipTicketSale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'auditable_type' => (new Ship)->getMorphClass(),
            'auditable_id' => fn (): int => Ship::create(['name' => fake()->company(), 'status' => 1])->id,
            'sale_id' => fn (array $attributes): ?int => $attributes['auditable_type'] === (new ShipTicketSale)->getMorphClass()
                ? (int) $attributes['auditable_id']
                : (isset($attributes['context']['sale_id']) ? (int) $attributes['context']['sale_id'] : null),
            'user_id' => null,
            'user_name' => null,
            'event' => 'updated',
            'old_values' => ['name' => 'Previous ship name'],
            'new_values' => ['name' => 'Updated ship name'],
        ];
    }
}
