<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageDailyAggregateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'usage_date' => fake()->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
            'units_total' => fake()->numberBetween(1, 10000),
            'event_count' => fake()->numberBetween(1, 100),
        ];
    }
}
