<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionSegment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Acme Analytics', 'Globex Services'] as $merchantName) {
            $merchant = Merchant::create(['name' => $merchantName]);

            $plans = collect([
                ['name' => 'Starter', 'base_price_paise' => 99900, 'included_units' => 10000, 'overage_rate_paise_per_unit' => 10],
                ['name' => 'Growth', 'base_price_paise' => 499900, 'included_units' => 100000, 'overage_rate_paise_per_unit' => 7],
                ['name' => 'Scale', 'base_price_paise' => 1499900, 'included_units' => 500000, 'overage_rate_paise_per_unit' => 4],
            ])->map(fn (array $attributes) => $merchant->plans()->create([
                ...$attributes,
                'billing_cycle' => 'monthly',
                'is_active' => true,
            ]));

            User::factory()->create([
                'merchant_id' => $merchant->id,
                'name' => $merchant->name.' Admin',
                'email' => strtolower(str_replace(' ', '.', $merchant->name)).'@example.test',
                'password' => 'password',
            ]);

            Customer::factory(50)->create(['merchant_id' => $merchant->id])->each(
                function (Customer $customer, int $index) use ($merchant, $plans): void {
                    $startedAt = $index % 4 === 0
                        ? Carbon::now('UTC')->startOfMonth()->addDays(10)
                        : Carbon::now('UTC')->startOfMonth();
                    $periodStart = $startedAt->copy()->startOfMonth();
                    $subscription = Subscription::create([
                        'customer_id' => $customer->id,
                        'merchant_id' => $merchant->id,
                        'status' => 'active',
                        'started_at' => $startedAt,
                        'current_period_start' => $periodStart,
                        'current_period_end' => $periodStart->copy()->addMonth()->subSecond(),
                    ]);

                    $plan = $plans[$index % $plans->count()];
                    SubscriptionSegment::create([
                        'subscription_id' => $subscription->id,
                        'plan_id' => $plan->id,
                        'starts_at' => $startedAt,
                        'ends_at' => null,
                    ]);
                }
            );
        }
    }
}
