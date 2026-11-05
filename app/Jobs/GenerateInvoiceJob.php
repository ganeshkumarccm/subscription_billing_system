<?php

namespace App\Jobs;

use App\DTOs\BillingPlanSnapshot;
use App\DTOs\BillingSegmentData;
use App\Services\InvoiceCalculator;
use DateTimeImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateInvoiceJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $uniqueFor = 86400;

    public function __construct(
        public readonly int $subscriptionId,
        public readonly string $periodStart,
    ) {
        $this->onQueue('billing');
    }

    public function uniqueId(): string
    {
        return $this->subscriptionId.':'.$this->periodStart;
    }

    public function handle(InvoiceCalculator $calculator): void
    {
        DB::transaction(function () use ($calculator): void {
            $subscription = DB::table('subscriptions')
                ->where('id', $this->subscriptionId)
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                return;
            }

            $periodStart = Carbon::parse($this->periodStart, 'UTC')->startOfDay();
            $existingInvoice = DB::table('invoices')
                ->where('subscription_id', $subscription->id)
                ->whereDate('period_start', $periodStart->toDateString())
                ->exists();

            if ($existingInvoice) {
                return;
            }

            $periodEnd = Carbon::parse($subscription->current_period_end, 'UTC')->startOfDay();
            $segments = DB::table('subscription_segments as ss')
                ->join('plans as p', 'p.id', '=', 'ss.plan_id')
                ->where('ss.subscription_id', $subscription->id)
                ->where('ss.starts_at', '<=', $periodEnd)
                ->where(function ($query) use ($periodStart): void {
                    $query->whereNull('ss.ends_at')->orWhere('ss.ends_at', '>=', $periodStart);
                })
                ->orderBy('ss.starts_at')
                ->get([
                    'ss.starts_at', 'ss.ends_at', 'p.id as plan_id',
                    'p.base_price_paise', 'p.included_units',
                    'p.overage_rate_paise_per_unit',
                ]);

            $billingSegments = $segments->map(function ($segment) use ($subscription, $periodStart, $periodEnd): BillingSegmentData {
                $start = max($periodStart, Carbon::parse($segment->starts_at, 'UTC')->startOfDay());
                $end = min($periodEnd, $segment->ends_at
                    ? Carbon::parse($segment->ends_at, 'UTC')->startOfDay()
                    : $periodEnd);
                $usage = DB::table('usage_daily_aggregates')
                    ->where('customer_id', $subscription->customer_id)
                    ->whereBetween('usage_date', [$start->toDateString(), $end->toDateString()])
                    ->sum('units_total');

                return new BillingSegmentData(
                    new BillingPlanSnapshot(
                        (int) $segment->plan_id,
                        (int) $segment->base_price_paise,
                        (int) $segment->included_units,
                        (int) $segment->overage_rate_paise_per_unit,
                    ),
                    new DateTimeImmutable($start->toDateString()),
                    new DateTimeImmutable($end->toDateString()),
                    (int) $usage,
                );
            })->all();

            if ($billingSegments === []) {
                return;
            }

            $calculation = $calculator->calculate($periodStart, $periodEnd, $billingSegments);
            $now = Carbon::now('UTC');
            $invoiceId = DB::table('invoices')->insertGetId([
                'subscription_id' => $subscription->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'subtotal' => $calculation->subtotalPaise,
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($calculation->items as $item) {
                DB::table('invoice_items')->insert([
                    'invoice_id' => $invoiceId,
                    'type' => $item->type,
                    'plan_id' => $item->planId,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unitPricePaise,
                    'amount' => $item->amountPaise,
                    'segment_start' => $item->segmentStart->format('Y-m-d'),
                    'segment_end' => $item->segmentEnd->format('Y-m-d'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $periodDays = $periodStart->diffInDays($periodEnd) + 1;
            $nextStart = $periodEnd->copy()->addDay();
            DB::table('subscriptions')->where('id', $subscription->id)->update([
                'current_period_start' => $nextStart,
                'current_period_end' => $nextStart->copy()->addDays($periodDays - 1),
                'updated_at' => $now,
            ]);
        });
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
