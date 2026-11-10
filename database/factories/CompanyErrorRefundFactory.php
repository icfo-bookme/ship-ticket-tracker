<?php

namespace Database\Factories;

use App\Models\CompanyErrorRefund;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompanyErrorRefund> */
class CompanyErrorRefundFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'loss_type' => 'partner_share',
            'loss_percent' => 20,
            'loss_amount' => 200,
            'reason' => fake()->sentence(),
            'created_by' => null,
        ];
    }

    public function fullLoss(): static
    {
        return $this->state(fn (array $attributes): array => [
            'loss_type' => 'custom', 'loss_percent' => 100, 'loss_amount' => 1000,
        ]);
    }
}
