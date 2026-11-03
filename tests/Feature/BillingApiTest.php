<?php

use App\Jobs\AggregateDailyUsageJob;
use App\Jobs\GenerateInvoiceJob;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\MerchantApiKey;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionSegment;
use App\Models\UsageDailyAggregate;
use App\Models\UsageEvent;
use App\Models\User;
use App\Services\DashboardQueryService;
use App\Services\MerchantContextService;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

beforeEach(function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
        'cache.default' => 'array',
        'queue.default' => 'sync',
        'jwt.blacklist_enabled' => true,
    ]);
    DB::purge('mysql');
    DB::purge('sqlite');
    Artisan::call('migrate:fresh', ['--database' => 'sqlite', '--force' => true]);
    Cache::flush();
});

function tenantFixture(): array
{
    $merchant = Merchant::factory()->create();
    $user = User::factory()->create(['merchant_id' => $merchant->id]);
    $plan = Plan::factory()->create([
        'merchant_id' => $merchant->id,
        'base_price_paise' => 10000,
        'included_units' => 100,
        'overage_rate_paise_per_unit' => 10,
    ]);
    $customer = Customer::factory()->create(['merchant_id' => $merchant->id]);
    $start = Carbon::now('UTC')->startOfDay()->subDay();
    $subscription = Subscription::factory()->create([
        'merchant_id' => $merchant->id,
        'customer_id' => $customer->id,
        'current_period_start' => $start,
        'current_period_end' => $start->copy()->addDays(30),
        'started_at' => $start,
        'status' => 'active',
    ]);
    SubscriptionSegment::factory()->create([
        'subscription_id' => $subscription->id,
        'plan_id' => $plan->id,
        'starts_at' => $start,
        'ends_at' => null,
    ]);
    $plainKey = 'test-key-'.Str::random(20);
    MerchantApiKey::create(['merchant_id' => $merchant->id, 'key_hash' => hash('sha256', $plainKey)]);

    return compact('merchant', 'user', 'plan', 'customer', 'subscription', 'start', 'plainKey');
}

function usagePayload(Customer $customer, int $units = 5): array
{
    return ['customer_id' => $customer->id, 'units' => $units, 'occurred_at' => Carbon::now('UTC')->toISOString()];
}

it('replays usage without double counting', function () {
    $fixture = tenantFixture();
    $headers = ['X-API-Key' => $fixture['plainKey'], 'Idempotency-Key' => 'replay-key'];

    $first = $this->withHeaders($headers)->postJson('/api/v1/usage', usagePayload($fixture['customer'], 7));
    $second = $this->withHeaders($headers)->postJson('/api/v1/usage', usagePayload($fixture['customer'], 7));

    $first->assertCreated();
    $second->assertOk()->assertJsonPath('created', false);
    expect(UsageEvent::count())->toBe(1)
        ->and(UsageDailyAggregate::first()->units_total)->toBe(7)
        ->and(UsageDailyAggregate::first()->event_count)->toBe(1);
});

it('rejects concurrent duplicate semantics without double counting', function () {
    $fixture = tenantFixture();
    $action = app(\App\Actions\RecordUsageEvent::class);
    $data = new \App\DTOs\RecordUsageData($fixture['merchant'], $fixture['customer']->id, 3, 'same-key', Carbon::now('UTC'));

    expect($action->execute($data)['created'])->toBeTrue()
        ->and($action->execute($data)['created'])->toBeFalse()
        ->and(UsageEvent::count())->toBe(1)
        ->and(UsageDailyAggregate::first()->units_total)->toBe(3);
});

it('returns 429 after five login attempts per email and IP', function () {
    $fixture = tenantFixture();
    $payload = ['email' => $fixture['user']->email, 'password' => 'wrong-password'];

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/login', $payload)->assertUnauthorized();
    }

    $this->postJson('/api/v1/auth/login', $payload)->assertStatus(429)->assertHeader('Retry-After');
});

it('rejects usage for a customer belonging to another merchant', function () {
    $first = tenantFixture();
    $second = tenantFixture();

    $this->withHeaders(['X-API-Key' => $first['plainKey'], 'Idempotency-Key' => 'wrong-merchant'])
        ->postJson('/api/v1/usage', usagePayload($second['customer']))
        ->assertStatus(422);
});

it('reconciles daily usage idempotently', function () {
    $fixture = tenantFixture();
    UsageEvent::create([
        'merchant_id' => $fixture['merchant']->id,
        'customer_id' => $fixture['customer']->id,
        'subscription_id' => $fixture['subscription']->id,
        'idempotency_key' => 'aggregate-event',
        'units' => 11,
        'usage_date' => now('UTC')->toDateString(),
        'occurred_at' => now('UTC'),
    ]);

    $job = new AggregateDailyUsageJob();
    $job->handle();
    $job->handle();

    expect(UsageDailyAggregate::count())->toBe(1)
        ->and(UsageDailyAggregate::first()->units_total)->toBe(11)
        ->and(UsageDailyAggregate::first()->event_count)->toBe(1);
});

it('retries invoice generation without creating a second invoice', function () {
    $fixture = tenantFixture();
    $job = new GenerateInvoiceJob($fixture['subscription']->id, $fixture['start']->toDateString());

    $job->handle(app(\App\Services\InvoiceCalculator::class));
    $job->handle(app(\App\Services\InvoiceCalculator::class));

    expect(DB::table('invoices')->where('subscription_id', $fixture['subscription']->id)->count())->toBe(1)
        ->and(DB::table('invoice_items')->count())->toBe(2);
});

it('returns the dashboard for a known seeded dataset', function () {
    $fixture = tenantFixture();
    UsageDailyAggregate::create([
        'customer_id' => $fixture['customer']->id,
        'usage_date' => now('UTC')->toDateString(),
        'units_total' => 40,
        'event_count' => 2,
    ]);

    $response = $this->withToken(auth('api')->login($fixture['user']))
        ->getJson('/api/v1/dashboard');

    $response->assertOk()
        ->assertJsonPath('data.current_cycle.usage_units', 40)
        ->assertJsonPath('data.top_customers.0.customer_id', $fixture['customer']->id);
});

it('rolls back merchant registration when user creation fails', function () {
    $existing = User::factory()->create();
    $before = Merchant::count();

    expect(fn () => app(\App\Actions\RegisterMerchantAction::class)->execute(
        new \App\DTOs\RegisterMerchantData('Rollback Merchant', 'Owner', $existing->email, 'password123')
    ))->toThrow(\Illuminate\Database\QueryException::class);

    expect(Merchant::count())->toBe($before);
});

it('returns distinct 401 responses for missing, tampered, and expired tokens', function () {
    $fixture = tenantFixture();
    $this->getJson('/api/v1/dashboard')->assertUnauthorized()->assertJsonPath('message', 'Authentication token is missing.');

    $this->withToken('tampered-token')->getJson('/api/v1/dashboard')
        ->assertUnauthorized()->assertJsonPath('message', 'Authentication token is invalid.');

    $expired = auth('api')->login($fixture['user']);
    Carbon::setTestNow(Carbon::now('UTC')->addHours(2));
    $this->withToken($expired)->getJson('/api/v1/dashboard')
        ->assertUnauthorized()->assertJsonPath('message', 'Authentication token has expired.');
    Carbon::setTestNow();
});

it('returns 403 when a token accesses another merchant route', function () {
    $first = tenantFixture();
    $second = tenantFixture();

    $this->withToken(auth('api')->login($first['user']))
        ->getJson('/api/v1/merchants/'.$second['merchant']->id.'/dashboard')
        ->assertForbidden();
});

it('invalidates a token on logout', function () {
    $fixture = tenantFixture();
    $token = auth('api')->login($fixture['user']);

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
    $this->withToken($token)->getJson('/api/v1/auth/me')
        ->assertUnauthorized()->assertJsonPath('message', 'Authentication token is invalid.');
});
