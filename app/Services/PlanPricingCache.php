<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class PlanPricingCache
{
    private const TTL_SECONDS = 600;
    private const VERSION_KEY = 'billing:plan-pricing-cache:version';

    public function planById(int $planId): ?Plan
    {
        $key = $this->versionedKey('plan', (string) $planId);

        return $this->rememberWithLock($key, fn () => Plan::query()->find($planId));
    }

    public function activePlanForSubscriptionAt(int $subscriptionId, Carbon $at): ?Plan
    {
        $dateKey = $at->copy()->utc()->format('Y-m-d\TH:i:s\Z');
        $key = $this->versionedKey('subscription-plan', $subscriptionId.':'.$dateKey);

        return $this->rememberWithLock($key, function () use ($subscriptionId, $at): ?Plan {
            return Plan::query()
                ->whereHas('subscriptionSegments', function ($query) use ($subscriptionId, $at): void {
                    $query->where('subscription_id', $subscriptionId)
                        ->where('starts_at', '<=', $at)
                        ->where(function ($query) use ($at): void {
                            $query->whereNull('ends_at')->orWhere('ends_at', '>', $at);
                        });
                })
                ->first();
        });
    }

    public function invalidate(): void
    {
        Cache::increment(self::VERSION_KEY);
    }

    private function versionedKey(string $type, string $identifier): string
    {
        $version = Cache::rememberForever(self::VERSION_KEY, fn (): int => 1);

        return "billing:plan-pricing:{$type}:{$identifier}:v{$version}";
    }

    private function rememberWithLock(string $key, callable $resolver): mixed
    {
        $store = Cache::store();
        $cached = $store->get($key);

        if ($cached !== null) {
            return $cached;
        }

        try {
            return $store->lock($key.':lock', 10)->block(5, function () use ($store, $key, $resolver): mixed {
                return $store->remember($key, self::TTL_SECONDS, $resolver);
            });
        } catch (LockTimeoutException $exception) {
            throw $exception;
        }
    }
}
