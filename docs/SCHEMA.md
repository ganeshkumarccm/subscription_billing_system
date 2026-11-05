# Database schema

All primary and foreign keys use unsigned big integers. Monetary fields are
integer paise, and timestamps are UTC. Foreign keys cascade when the child is
owned by its parent; plan references on historical segments and invoice items
use `RESTRICT` so billing history cannot be deleted accidentally.

## High-volume usage events

`usage_events` is the append-only ingestion table. At 50L+ (5M+) rows it
should be queried with tenant/customer/date predicates backed by the composite
indexes, not by scanning the table or loading events into application memory.
Ingestion should use an atomic insert with the
`(customer_id, idempotency_key)` unique key, then update the corresponding
daily row with an atomic upsert.

`usage_daily_aggregates` stores one row per customer and UTC usage date. Its
primary key makes the daily counter upsertable and keeps dashboard charts,
period totals, and invoice usage calculations proportional to days/customers
instead of individual events. `event_count` supports volume reporting without
recounting the raw event table. Raw events remain available for audit and
idempotency.

## Partitioning plan

If event volume or retention requires it, partition `usage_events` by monthly
`RANGE` on `usage_date`, for example:

```sql
PARTITION BY RANGE COLUMNS (usage_date) (
    PARTITION p202611 VALUES LESS THAN ('2026-12-01'),
    PARTITION p202612 VALUES LESS THAN ('2027-01-01'),
    PARTITION pmax VALUES LESS THAN (MAXVALUE)
);
```

The application would create the next month ahead of time, archive/drop old
partitions according to retention, and keep date-bounded queries
partition-prunable. Trade-offs are more operational DDL, care around UTC
boundaries, restrictions on foreign keys and partition changes, and more
complex backups/restores. Partitioning does not replace the composite indexes
or daily aggregate table.

MySQL requires every `UNIQUE` or `PRIMARY KEY` on a partitioned table to
contain the partitioning column. Therefore the current global idempotency
constraint `(customer_id, idempotency_key)` cannot be retained unchanged on a
partitioned `usage_events` table. Before partitioning, choose one of:

1. Change the unique key to include `usage_date` and enforce that an
   idempotency key is date-scoped.
2. Keep global idempotency in a small unpartitioned key table and use
   `(customer_id, idempotency_key, usage_date)` on the partitioned events.
3. Keep `usage_events` unpartitioned if global idempotency is mandatory and
   partitioning complexity is not justified.

The second option preserves global idempotency while allowing monthly event
partitions, at the cost of an additional write and lookup path.
