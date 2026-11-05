<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageEventFactory extends Factory
{
    public function definition(): array
    {
        $occurredAt = fake()->dateTimeBetween('-90 days', 'now');
        return [
            'merchant_id' => Merchant::factory(),
            'customer_id' => Customer::factory(),
            'subscription_id' => Subscription::factory(),
            'idempotency_key' => fake()->unique()->uuid(),
            'units' => fake()->numberBetween(1, 100),
            'usage_date' => $occurredAt->format('Y-m-d'),
            'occurred_at' => $occurredAt,
        ];
    }
}
