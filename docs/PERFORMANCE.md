# Performance review at 5M+ usage events

## Benchmark

The k6 script [scripts/k6-usage.js](../scripts/k6-usage.js) exercises
`POST /api/v1/usage` with unique idempotency keys and realistic concurrent
writers. It accepts `201` and `429`; the latter makes rate-limit behavior
visible instead of treating protection as an application failure.

```powershell
k6 run -e BASE_URL=http://localhost:8000 `
  -e API_KEY=replace-with-plain-key `
  -e CUSTOMER_ID=1 `
  -e VUS=20 -e DURATION=60s `
  scripts/k6-usage.js
```

Run a baseline first, then increase VUs gradually. Record p50/p95/p99 latency,
accepted requests/sec, 429 rate, MySQL CPU/IO, Redis latency, lock waits, and
the queue depth. Do not benchmark with the same idempotency key: that measures
replay handling rather than write throughput.

## EXPLAIN plans

Run these against the populated MySQL 8 database with representative IDs and
dates. Prefer `EXPLAIN ANALYZE` in staging after the data distribution matches
production.

### Dashboard active subscriptions

```sql
EXPLAIN
SELECT s.id, s.customer_id, s.current_period_start, s.current_period_end
FROM subscriptions AS s
JOIN customers AS c ON c.id = s.customer_id
WHERE s.merchant_id = 42
  AND s.status = 'active';
```

Recommended index:

```sql
CREATE INDEX subscriptions_merchant_status_end_customer
ON subscriptions (merchant_id, status, current_period_end, customer_id);
```

The dashboard should use a merchant/status range lookup rather than scanning
all subscriptions.

### Dashboard daily aggregates

```sql
EXPLAIN
SELECT customer_id, usage_date, units_total
FROM usage_daily_aggregates
WHERE customer_id IN (101, 102, 103)
  AND usage_date >= '2026-10-05'
  AND usage_date <= '2026-11-03';
```

The existing primary key `(customer_id, usage_date)` is appropriate for this
query. Use sargable comparisons, not `WHERE DATE(usage_date) ...`; wrapping a
column in a function can prevent efficient range access. The existing
`(usage_date, customer_id)` index supports date-first reporting.

### Dashboard segments and plans

```sql
EXPLAIN
SELECT ss.subscription_id, ss.plan_id, ss.starts_at, ss.ends_at,
       p.included_units, p.overage_rate_paise_per_unit
FROM subscription_segments AS ss
JOIN plans AS p ON p.id = ss.plan_id
WHERE ss.subscription_id IN (1001, 1002, 1003)
ORDER BY ss.starts_at;
```

The existing `(subscription_id, starts_at, ends_at)` index supports this
lookup. The plan primary key supports the join.

### Invoice segment usage

```sql
EXPLAIN
SELECT SUM(units_total)
FROM usage_daily_aggregates
WHERE customer_id = 101
  AND usage_date >= '2026-10-01'
  AND usage_date <= '2026-10-31';
```

The aggregate primary key supports a tight range scan. However, the current
`GenerateInvoiceJob` executes this query once per segment. At scale, load all
period aggregates for the customer once, group them in memory by date, and
calculate each segment's sum without issuing one query per segment.

### Cycle-end dispatcher

```sql
EXPLAIN
SELECT id, current_period_start
FROM subscriptions
WHERE status = 'active'
  AND current_period_end <= '2026-11-03'
  AND id > 0
ORDER BY id
LIMIT 500;
```

Recommended index:

```sql
CREATE INDEX subscriptions_status_period_end_id
ON subscriptions (status, current_period_end, id);
```

`chunkById` still needs the ID ordering/filter. If the tenant dashboard query
is the dominant path, retain the merchant-leading index as well; verify both
with `EXPLAIN` before adding redundant indexes.

## Missing indexes and query shape

1. Add `(merchant_id, status, current_period_end, customer_id)` to support
   active subscription dashboard reads.
2. Add `(status, current_period_end, id)` for the daily cycle-end dispatcher.
3. Keep the existing aggregate primary key and date-first secondary index.
4. Replace dashboard `whereDate` predicates with direct date comparisons.
5. Remove the invoice job's per-segment aggregate query (an N+1 query pattern)
   by loading the customer's period aggregates once.
6. Avoid adding indexes to `usage_events` speculatively: every extra secondary
   index increases insert cost and buffer-pool pressure. The current indexes
   cover idempotency, merchant/date reporting, subscription/date billing, and
   customer/date drill-down.

The dashboard currently performs a bounded set of database queries and loops
over already-loaded collections in PHP; it does not issue an Eloquent query
inside the customer loop. Its memory cost is proportional to active customers
times roughly 60 days, not to 5M raw events. The 60-second merchant cache is
important for repeated dashboard reads.

## `usage_daily_aggregates` hot-row contention

The atomic upsert is correct, but many concurrent events for the same
customer/day serialize on the same primary-key row. This is most visible for a
large tenant or a small number of very active customers:

```text
INSERT ... ON DUPLICATE KEY UPDATE units_total = units_total + ?,
                                    event_count = event_count + 1
```

Options, in increasing complexity:

1. Keep the synchronous aggregate update for correctness and measure lock
   waits first. Ensure the transaction is short and the aggregate index is
   present.
2. Buffer deltas in Redis using atomic `HINCRBY` fields keyed by
   `customer_id|usage_date`. A once-per-minute flusher drains Redis in batches
   and applies SQL upserts. This reduces MySQL row contention but dashboards
   become eventually consistent and the flusher needs crash-safe draining,
   retry, and duplicate-flush handling.
3. Write immutable per-event deltas to a queue/stream and have partitioned
   consumers aggregate by customer/day. This improves write smoothing but adds
   operational complexity and lag monitoring.
4. Shard the aggregate key with N buckets, increment
   `(customer_id, usage_date, bucket)`, and sum buckets at read time. This
   reduces a single hot row but changes the schema and every aggregate query.

For this assignment I would retain the synchronous atomic upsert, measure
contention, and only introduce Redis buffering when lock waits or p95 latency
show it is necessary.

## What I would do with more time

- **Monthly partitioning:** partition `usage_events` by `RANGE` on
  `usage_date`, pre-create future partitions, and archive/drop old ones.
  Revisit the global idempotency unique-key design because MySQL requires every
  unique key on a partitioned table to include the partition column.
- **Read replica:** route dashboard and reporting reads to a replica with an
  explicit freshness expectation; keep usage writes, idempotency checks,
  aggregates, and invoice transactions on the primary.
- **Laravel Octane:** benchmark Octane only after query/lock work. It can reduce
  PHP bootstrap overhead for dashboard/API traffic, but requires strict
  request-state hygiene and does not remove MySQL contention.
- Add `EXPLAIN ANALYZE` regression checks and production telemetry for query
  latency, rows examined, deadlocks, lock waits, replica lag, Redis latency,
  queue lag, and cache hit rate.
- Move dashboard calculations into SQL aggregate queries or a precomputed
  merchant summary if active-customer cardinality makes the current PHP
  collection pass expensive.
