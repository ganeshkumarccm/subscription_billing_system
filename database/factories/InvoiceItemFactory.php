<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->startOfMonth();
        return [
            'invoice_id' => Invoice::factory(),
            'type' => fake()->randomElement(['base', 'overage']),
            'plan_id' => Plan::factory(),
            'description' => fake()->sentence(4),
            'quantity' => fake()->numberBetween(1, 1000),
            'unit_price' => fake()->numberBetween(1, 100000),
            'amount' => fake()->numberBetween(1, 1000000),
            'segment_start' => $start->toDateString(),
            'segment_end' => $start->copy()->endOfMonth()->toDateString(),
        ];
    }
}
