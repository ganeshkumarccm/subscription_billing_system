<?php

namespace App\Actions;

use App\DTOs\CreateSubscriptionData;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateSubscription
{
    public function execute(CreateSubscriptionData $data): Subscription
    {
        return DB::transaction(function () use ($data): Subscription {
            $customer = $data->merchant->customers()
                ->whereKey($data->customerId)
                ->lockForUpdate()
                ->first();

            if (! $customer) {
                throw ValidationException::withMessages([
                    'customer_id' => 'The customer does not belong to this merchant.',
                ]);
            }

            if ($customer->subscriptions()->where('status', 'active')->exists()) {
                throw ValidationException::withMessages([
                    'customer_id' => 'The customer already has an active subscription.',
                ]);
            }

            $plan = $data->merchant->plans()
                ->whereKey($data->planId)
                ->where('is_active', true)
                ->first();

            if (! $plan) {
                throw ValidationException::withMessages([
                    'plan_id' => 'The plan must be an active plan belonging to this merchant.',
                ]);
            }

            $periodStart = Carbon::now('UTC')->startOfDay();
            $periodEnd = $plan->billing_cycle === 'yearly'
                ? $periodStart->copy()->addYear()->subSecond()
                : $periodStart->copy()->addMonth()->subSecond();

            $subscription = $customer->subscriptions()->create([
                'merchant_id' => $data->merchant->id,
                'status' => 'active',
                'started_at' => $periodStart,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
            ]);

            $subscription->segments()->create([
                'plan_id' => $plan->id,
                'starts_at' => $periodStart,
                'ends_at' => null,
            ]);

            return $subscription->load('segments.plan');
        });
    }
}
