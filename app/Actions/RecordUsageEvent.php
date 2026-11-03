<?php

namespace App\Actions;

use App\DTOs\RecordUsageData;
use App\Models\Subscription;
use App\Models\UsageEvent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RecordUsageEvent
{
    /**
     * @return array{event: UsageEvent, created: bool}
     */
    public function execute(RecordUsageData $data): array
    {
        return DB::transaction(function () use ($data): array {
            $existingEvent = UsageEvent::query()
                ->where('merchant_id', $data->merchant->id)
                ->where('customer_id', $data->customerId)
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();

            if ($existingEvent) {
                return ['event' => $existingEvent, 'created' => false];
            }

            $subscription = Subscription::query()
                ->where('merchant_id', $data->merchant->id)
                ->where('customer_id', $data->customerId)
                ->where('status', 'active')
                ->where('current_period_start', '<=', $data->occurredAt)
                ->where('current_period_end', '>=', $data->occurredAt)
                ->first();

            if (! $subscription) {
                throw ValidationException::withMessages([
                    'customer_id' => 'The customer must belong to this merchant and have an active subscription covering occurred_at.',
                ]);
            }

            $hasSegment = $subscription->segments()
                ->where('starts_at', '<=', $data->occurredAt)
                ->where(function ($query) use ($data): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>', $data->occurredAt);
                })
                ->exists();

            if (! $hasSegment) {
                throw ValidationException::withMessages([
                    'occurred_at' => 'No subscription plan segment covers occurred_at.',
                ]);
            }

            $usageDate = $data->occurredAt->copy()->utc()->toDateString();
            $now = Carbon::now('UTC');
            $inserted = DB::table('usage_events')->insertOrIgnore([
                'merchant_id' => $data->merchant->id,
                'customer_id' => $data->customerId,
                'subscription_id' => $subscription->id,
                'idempotency_key' => $data->idempotencyKey,
                'units' => $data->units,
                'usage_date' => $usageDate,
                'occurred_at' => $data->occurredAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($inserted === 1) {
                if (DB::connection()->getDriverName() === 'sqlite') {
                    DB::statement(
                        'INSERT INTO usage_daily_aggregates '
                        .'(customer_id, usage_date, units_total, event_count, created_at, updated_at) '
                        .'VALUES (?, ?, ?, 1, ?, ?) '
                        .'ON CONFLICT(customer_id, usage_date) DO UPDATE SET '
                        .'units_total = usage_daily_aggregates.units_total + excluded.units_total, '
                        .'event_count = usage_daily_aggregates.event_count + 1, updated_at = excluded.updated_at',
                        [$data->customerId, $usageDate, $data->units, $now, $now],
                    );
                } else {
                    DB::statement(
                        'INSERT INTO usage_daily_aggregates '
                        .'(customer_id, usage_date, units_total, event_count, created_at, updated_at) '
                        .'VALUES (?, ?, ?, 1, ?, ?) '
                        .'ON DUPLICATE KEY UPDATE '
                        .'units_total = units_total + ?, event_count = event_count + 1, updated_at = ?',
                        [$data->customerId, $usageDate, $data->units, $now, $now, $data->units, $now],
                    );
                }
            }

            $event = UsageEvent::query()
                ->where('customer_id', $data->customerId)
                ->where('idempotency_key', $data->idempotencyKey)
                ->firstOrFail();

            return ['event' => $event, 'created' => $inserted === 1];
        });
    }
}
