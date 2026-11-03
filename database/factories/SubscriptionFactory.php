<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $start = Carbon::now()->subDays(fake()->numberBetween(0, 60))->startOfDay();
        return [
            'customer_id' => Customer::factory(),
            'merchant_id' => Merchant::factory(),
            'status' => 'active',
            'started_at' => $start,
            'current_period_start' => $start->copy()->startOfMonth(),
            'current_period_end' => $start->copy()->startOfMonth()->addMonth()->subSecond(),
        ];
    }
}
