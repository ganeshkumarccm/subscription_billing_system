<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Services\PlanPricingCache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlanPricingCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
        ]);
        DB::purge('mysql');
        DB::purge('sqlite');
        Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
    }

    public function test_updating_a_plan_busts_the_cached_plan_lookup(): void
    {
        config(['cache.default' => 'array']);

        $plan = Plan::factory()->create(['base_price_paise' => 1000]);
        $cache = app(PlanPricingCache::class);

        self::assertSame(1000, $cache->planById($plan->id)->base_price_paise);

        $plan->update(['base_price_paise' => 2000]);

        self::assertSame(2000, $cache->planById($plan->id)->base_price_paise);
        self::assertSame(2000, Plan::query()->findOrFail($plan->id)->base_price_paise);
    }
}
