# Queue design

## Daily aggregation

`AggregateDailyUsageJob` recomputes daily totals with a SQL `GROUP BY
customer_id, usage_date` and an `INSERT ... ON DUPLICATE KEY UPDATE`. Running
it repeatedly is safe and supports reconciliation/backfill after ingestion
retries or an outage. Optional date bounds limit a repair to a range.

## Cycle-end invoicing

The daily `billing:dispatch-cycle-end-invoices` command finds active
subscriptions whose period ends on or before the current UTC date. It uses
`chunkById(500)` to keep memory bounded and avoid offset pagination while rows
are being updated. Each 500-row chunk becomes a separate Laravel bus batch,
and every `GenerateInvoiceJob` is sent to the `billing` queue.

Five hundred is large enough to amortize batch-dispatch overhead but small
enough that a failed batch is inexpensive to retry and the dispatcher does not
hold thousands of models/jobs in memory.

`GenerateInvoiceJob` is unique by subscription and period start, has three
attempts with exponential-style backoff, and records failures through
`failed()`. It locks the subscription, checks the invoice unique key, writes
the invoice and items in one transaction, and only then rolls the subscription
to its next period. A retry or duplicate dispatch therefore cannot create a
second invoice or roll the period twice.
