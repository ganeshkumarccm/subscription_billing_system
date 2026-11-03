<?php

namespace App\Providers;

use App\Models\Plan;
use App\Models\SubscriptionSegment;
use App\Observers\PlanObserver;
use App\Observers\SubscriptionSegmentObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Keep indexed strings within MySQL's legacy 1000-byte key limit.
        Schema::defaultStringLength(191);

        Plan::observe(PlanObserver::class);
        SubscriptionSegment::observe(SubscriptionSegmentObserver::class);

        RateLimiter::for('usage', function (Request $request) {
            $apiKey = $request->attributes->get('merchant_api_key');
            $key = $apiKey?->getKey() ?? $request->ip();

            return Limit::perMinute(120)->by('usage-api-key:'.$key);
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(
                'login:'.strtolower((string) $request->input('email')).'|'.$request->ip()
            );
        });
    }
}
