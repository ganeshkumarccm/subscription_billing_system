<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SeedUsageCommand extends Command
{
    protected $signature = 'seed:usage {--rows=5000000 : Number of usage events to insert}';

    protected $description = 'Bulk-insert usage events and update daily aggregates for performance testing';

    public function handle(): int
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->error('seed:usage requires a MySQL connection.');
            return self::FAILURE;
        }

        $rows = filter_var($this->option('rows'), FILTER_VALIDATE_INT);
        if ($rows === false || $rows < 0) {
            throw new InvalidArgumentException('The --rows option must be a non-negative integer.');
        }

        $pairs = DB::table('subscriptions')
            ->join('customers', 'customers.id', '=', 'subscriptions.customer_id')
            ->select([
                'subscriptions.id as subscription_id',
                'subscriptions.merchant_id',
                'subscriptions.customer_id',
            ])
            ->get();

        if ($pairs->isEmpty() && $rows > 0) {
            $this->error('No subscriptions found. Run db:seed first.');
            return self::FAILURE;
        }

        $chunkSize = 5000;
        $runId = bin2hex(random_bytes(8));
        $inserted = 0;
        $this->output->progressStart($rows);

        while ($inserted < $rows) {
            $count = min($chunkSize, $rows - $inserted);
            $events = [];
            $aggregates = [];
            $now = Carbon::now('UTC');

            for ($offset = 0; $offset < $count; $offset++) {
                $pair = $pairs[mt_rand(0, $pairs->count() - 1)];
                $occurredAt = $now->copy()->subDays(mt_rand(0, 89))->setTime(mt_rand(0, 23), mt_rand(0, 59), mt_rand(0, 59));
                $usageDate = $occurredAt->toDateString();
                $units = mt_rand(1, 100);

                $events[] = [
                    'merchant_id' => $pair->merchant_id,
                    'customer_id' => $pair->customer_id,
                    'subscription_id' => $pair->subscription_id,
                    'idempotency_key' => "seed-{$runId}-".($inserted + $offset),
                    'units' => $units,
                    'usage_date' => $usageDate,
                    'occurred_at' => $occurredAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $aggregateKey = $pair->customer_id.'|'.$usageDate;
                $aggregates[$aggregateKey] ??= [
                    'customer_id' => $pair->customer_id,
                    'usage_date' => $usageDate,
                    'units_total' => 0,
                    'event_count' => 0,
                ];
                $aggregates[$aggregateKey]['units_total'] += $units;
                $aggregates[$aggregateKey]['event_count']++;
            }

            DB::table('usage_events')->insert($events);
            $this->upsertAggregates(array_values($aggregates), $now);
            $inserted += $count;
            $this->output->progressAdvance($count);
        }

        $this->output->progressFinish();
        $this->info("Inserted {$rows} usage events.");

        return self::SUCCESS;
    }

    /**
     * Increment counters atomically so concurrent seeders cannot lose updates.
     *
     * @param array<int, array{customer_id:int, usage_date:string, units_total:int, event_count:int}> $aggregates
     */
    private function upsertAggregates(array $aggregates, Carbon $now): void
    {
        if ($aggregates === []) {
            return;
        }

        $values = [];
        $bindings = [];
        foreach ($aggregates as $aggregate) {
            $values[] = '(?, ?, ?, ?, ?, ?)';
            array_push(
                $bindings,
                $aggregate['customer_id'],
                $aggregate['usage_date'],
                $aggregate['units_total'],
                $aggregate['event_count'],
                $now,
                $now,
            );
        }

        DB::statement(
            'INSERT INTO usage_daily_aggregates '
            .'(customer_id, usage_date, units_total, event_count, created_at, updated_at) VALUES '
            .implode(', ', $values)
            .' ON DUPLICATE KEY UPDATE '
            .'units_total = units_total + VALUES(units_total), '
            .'event_count = event_count + VALUES(event_count), '
            .'updated_at = VALUES(updated_at)',
            $bindings,
        );
    }
}
