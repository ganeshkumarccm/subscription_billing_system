<?php

namespace App\Actions;

use App\Mail\SubscriptionStopped;
use App\Models\Merchant;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class CancelSubscription
{
    public function execute(Merchant $merchant, int $subscriptionId): Subscription
    {
        $subscription = DB::transaction(function () use ($merchant, $subscriptionId): Subscription {
            $subscription = Subscription::query()
                ->whereKey($subscriptionId)
                ->where('merchant_id', $merchant->id)
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                throw ValidationException::withMessages([
                    'subscription_id' => 'The subscription does not belong to this merchant.',
                ]);
            }

            if ($subscription->status !== 'active') {
                throw ValidationException::withMessages([
                    'subscription_id' => 'Only an active subscription can be stopped.',
                ]);
            }

            $cancelledAt = Carbon::now('UTC');
            $subscription->update(['status' => 'cancelled']);
            $subscription->segments()
                ->where('starts_at', '<=', $cancelledAt)
                ->whereNull('ends_at')
                ->update(['ends_at' => $cancelledAt]);

            return $subscription->load('segments.plan');
        });

        Mail::to($subscription->customer->email)->send(new SubscriptionStopped($subscription->load('customer')));

        return $subscription;
    }
}
