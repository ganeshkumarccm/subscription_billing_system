<?php

namespace Database\Factories;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'name' => fake()->unique()->words(2, true),
            'base_price_paise' => fake()->numberBetween(99900, 999900),
            'billing_cycle' => fake()->randomElement(['monthly', 'yearly']),
            'included_units' => fake()->numberBetween(1000, 100000),
            'overage_rate_paise_per_unit' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
