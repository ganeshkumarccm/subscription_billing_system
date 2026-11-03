<?php

namespace App\Actions;

use App\DTOs\ChangeSubscriptionPlanData;
use App\Models\Subscription;
use App\Services\PlanPricingCache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ChangeSubscriptionPlan
{
    public function __construct(private readonly PlanPricingCache $planPricingCache)
    {
    }

    public function execute(ChangeSubscriptionPlanData $data): Subscription
    {
        return DB::transaction(function () use ($data): Subscription {
            $now = Carbon::now('UTC');

            if ($data->changeAt->lt($now)) {
                throw ValidationException::withMessages([
                    'change_at' => 'The plan change cannot be in the past.',
                ]);
            }

            $subscription = Subscription::query()
                ->whereKey($data->subscriptionId)
                ->where('merchant_id', $data->merchant->id)
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                throw ValidationException::withMessages([
                    'subscription_id' => 'The subscription does not belong to this merchant.',
                ]);
            }

            if ($subscription->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'subscription_id' => 'A cancelled subscription cannot change plans.',
                ]);
            }

            if ($data->changeAt->lt($subscription->current_period_start)
                || $data->changeAt->gt($subscription->current_period_end)) {
                throw ValidationException::withMessages([
                    'change_at' => 'The plan change must be within the current billing period.',
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

            $segment = $subscription->segments()
                ->where('starts_at', '<=', $data->changeAt)
                ->where(function ($query) use ($data): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>', $data->changeAt);
                })
                ->orderByDesc('starts_at')
                ->lockForUpdate()
                ->first();

            if (! $segment) {
                throw ValidationException::withMessages([
                    'change_at' => 'No active subscription segment exists at the change time.',
                ]);
            }

            if ((int) $segment->plan_id === $plan->id) {
                throw ValidationException::withMessages([
                    'plan_id' => 'The subscription is already on this plan at the change time.',
                ]);
            }

            $segment->update(['ends_at' => $data->changeAt]);
            $subscription->segments()->create([
                'plan_id' => $plan->id,
                'starts_at' => $data->changeAt,
                'ends_at' => null,
            ]);

            $this->planPricingCache->invalidate();

            return $subscription->load('segments.plan');
        });
    }
}
