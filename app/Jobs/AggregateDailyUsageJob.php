<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateDailyUsageJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $uniqueFor = 3600;

    public function __construct(
        public readonly ?string $fromDate = null,
        public readonly ?string $toDate = null,
    ) {
        $this->onQueue('billing');
    }

    public function handle(): void
    {
        $from = $this->fromDate ? Carbon::parse($this->fromDate, 'UTC')->toDateString() : null;
        $to = $this->toDate ? Carbon::parse($this->toDate, 'UTC')->toDateString() : null;
        $now = Carbon::now('UTC');

        $where = [];
        $eventBindings = [];
        if ($from !== null) {
            $where[] = 'usage_date >= ?';
            $eventBindings[] = $from;
        }

        if ($to !== null) {
            $where[] = 'usage_date <= ?';
            $eventBindings[] = $to;
        }
        $whereClause = $where === [] ? '' : ' WHERE '.implode(' AND ', $where);

        DB::transaction(function () use ($whereClause, $eventBindings, $from, $to, $now): void {
            if (DB::connection()->getDriverName() === 'sqlite') {
                $query = DB::table('usage_events');
                if ($from !== null) {
                    $query->where('usage_date', '>=', $from);
                }
                if ($to !== null) {
                    $query->where('usage_date', '<=', $to);
                }

                $rows = $query->selectRaw('customer_id, usage_date, SUM(units) as units_total, COUNT(*) as event_count')
                    ->groupBy('customer_id', 'usage_date')->get();
                foreach ($rows as $row) {
                    DB::table('usage_daily_aggregates')->updateOrInsert(
                        ['customer_id' => $row->customer_id, 'usage_date' => $row->usage_date],
                        ['units_total' => $row->units_total, 'event_count' => $row->event_count, 'created_at' => $now, 'updated_at' => $now],
                    );
                }

                $stale = DB::table('usage_daily_aggregates');
                if ($from !== null) {
                    $stale->where('usage_date', '>=', $from);
                }
                if ($to !== null) {
                    $stale->where('usage_date', '<=', $to);
                }
                $valid = $rows->map(fn ($row) => $row->customer_id.'|'.$row->usage_date)->all();
                $stale->get()->each(function ($row) use ($valid): void {
                    if (! in_array($row->customer_id.'|'.$row->usage_date, $valid, true)) {
                        DB::table('usage_daily_aggregates')
                            ->where('customer_id', $row->customer_id)
                            ->where('usage_date', $row->usage_date)
                            ->delete();
                    }
                });
                return;
            }

            DB::statement(
                'INSERT INTO usage_daily_aggregates '
                .'(customer_id, usage_date, units_total, event_count, created_at, updated_at) '
                .'SELECT customer_id, usage_date, SUM(units), COUNT(*), ?, ? '
                .'FROM usage_events'.$whereClause
                .' GROUP BY customer_id, usage_date '
                .'ON DUPLICATE KEY UPDATE '
                .'units_total = VALUES(units_total), '
                .'event_count = VALUES(event_count), '
                .'updated_at = VALUES(updated_at)',
                array_merge([$now, $now], $eventBindings),
            );

            $staleWhere = [];
            $staleBindings = [];
            if ($from !== null) {
                $staleWhere[] = 'a.usage_date >= ?';
                $staleBindings[] = $from;
            }
            if ($to !== null) {
                $staleWhere[] = 'a.usage_date <= ?';
                $staleBindings[] = $to;
            }
            $staleClause = $staleWhere === [] ? '' : ' AND '.implode(' AND ', $staleWhere);

            DB::statement(
                'DELETE a FROM usage_daily_aggregates a '
                .'LEFT JOIN (SELECT DISTINCT customer_id, usage_date FROM usage_events'.$whereClause.') e '
                .'ON e.customer_id = a.customer_id AND e.usage_date = a.usage_date '
                .'WHERE e.customer_id IS NULL'.$staleClause,
                array_merge($eventBindings, $staleBindings),
            );
        });
    }

    public function uniqueId(): string
    {
        return ($this->fromDate ?? '*').':'.($this->toDate ?? '*');
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function failed(\Throwable $exception): void
    {
        report($exception);
    }
}
