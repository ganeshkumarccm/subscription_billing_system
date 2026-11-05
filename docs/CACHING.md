# Plan pricing cache

`App\Services\PlanPricingCache` caches:

- Plan lookups by plan ID.
- The active plan for a subscription at a UTC timestamp.

Entries use the Redis cache store, a 10-minute TTL, and `Cache::remember`. A
per-key `Cache::lock` prevents concurrent requests from loading the same hot
key at once. The cache uses a version key instead of tags so invalidation also
works with cache stores used by tests.

## Invalidation

`PlanObserver` increments the pricing-cache version after a plan is updated or
deleted. `SubscriptionSegmentObserver` increments it when a segment is created,
updated, or deleted. New cache keys include the current version, so all plan
and active-segment lookups immediately stop using entries from the old version.
Old Redis entries expire naturally within their original TTL.

## Billing rule

Invoice generation must always read plan prices from the database, inside its
billing transaction. It must never use `PlanPricingCache`, because invoices
are financial records and require the authoritative persisted price.
