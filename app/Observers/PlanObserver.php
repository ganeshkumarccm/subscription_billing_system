<?php

namespace App\Observers;

use App\Models\Plan;
use App\Services\PlanPricingCache;

class PlanObserver
{
    public function updated(Plan $plan): void
    {
        app(PlanPricingCache::class)->invalidate();
    }

    public function deleted(Plan $plan): void
    {
        app(PlanPricingCache::class)->invalidate();
    }
}
