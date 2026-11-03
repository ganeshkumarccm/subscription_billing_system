<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionSegmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'plan_id' => Plan::factory(),
            'starts_at' => now()->startOfMonth(),
            'ends_at' => null,
        ];
    }
}
