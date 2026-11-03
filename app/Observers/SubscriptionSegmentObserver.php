<?php

namespace App\Observers;

use App\Models\SubscriptionSegment;
use App\Services\PlanPricingCache;

class SubscriptionSegmentObserver
{
    public function created(SubscriptionSegment $segment): void
    {
        app(PlanPricingCache::class)->invalidate();
    }

    public function updated(SubscriptionSegment $segment): void
    {
        app(PlanPricingCache::class)->invalidate();
    }

    public function deleted(SubscriptionSegment $segment): void
    {
        app(PlanPricingCache::class)->invalidate();
    }
}
