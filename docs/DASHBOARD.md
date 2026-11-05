# Dashboard queries and EXPLAIN

Dashboard results are cached per merchant for 60 seconds and all usage metrics
read `usage_daily_aggregates`, never `usage_events`.

Representative MySQL 8 plans to inspect in the target database:

```sql
EXPLAIN SELECT customer_id, usage_date, units_total
FROM usage_daily_aggregates
WHERE customer_id IN (?)
  AND usage_date BETWEEN '2026-10-05' AND '2026-11-03';
```

Expected access: the primary key `(customer_id, usage_date)` is used with
`type=range`; no full table scan should occur for known customers.

```sql
EXPLAIN SELECT customer_id, usage_date, SUM(units_total)
FROM usage_daily_aggregates
WHERE usage_date BETWEEN '2026-10-05' AND '2026-11-03'
GROUP BY customer_id, usage_date;
```

Expected access: the `usage_date, customer_id` composite index is eligible for
the date range and grouping. Run `EXPLAIN ANALYZE` in staging with production
cardinality before changing indexes.
