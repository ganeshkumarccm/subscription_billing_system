# Assumptions

- Local PHP serves the application at `http://localhost:8000`; MySQL and Redis
  run as local services.
- MySQL 8.4 is used as a MySQL 8-compatible image. The primary database is
  `billing`; Pest uses a separate `billing_test` database.
- The MySQL test database `billing_test` is created separately and is
  intentionally not populated with production data.
- Host-run tests use the local MySQL connection configured in `.env.testing`.
- The database cache store is used locally so migrations and seeders do not
  require a running Redis service. Queue workers use Redis when started
  explicitly with `php artisan queue:work redis`.
- API installation uses Laravel Sanctum's generated API scaffolding as the
  Laravel 11 baseline. JWT authentication and the usage endpoint's API-key
  middleware will be implemented in the application layer.
- All application timestamps are stored and interpreted as UTC. Money values
  are integer paise.
- A mid-cycle plan change is represented by subscription segments; each segment
  is billed using its own plan rate and day-based proration.
- Usage events are accepted late when `occurred_at` falls within the customer's
  current active subscription period; events outside that period are rejected.
- A usage event may contain from 1 through 1,000,000 units. This protects the
  ingestion endpoint from accidental or abusive oversized writes.
- `Idempotency-Key` is scoped to a customer, matching the database uniqueness
  constraint. A replay returns the originally stored event and does not update
  daily aggregates.
- Plan changes use an optional UTC `change_at` timestamp and default to the
  current UTC time. Segments are half-open intervals: a segment includes its
  start and excludes its `ends_at`, so usage exactly at a change boundary is
  attributed to the new plan.
- A change at midnight belongs to the new UTC calendar day and opens the new
  segment at exactly `00:00:00`.
- Several changes in one billing cycle are allowed. Each change closes the
  segment active at that timestamp and creates a new segment; changes cannot be
  made in the past or to a cancelled subscription.
- A change on the last day is allowed through the current period end. The
  resulting segment is billed only for the remaining day-based portion of the
  period.
