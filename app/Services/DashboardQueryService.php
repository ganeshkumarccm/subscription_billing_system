<?php

namespace App\Services;

use App\Models\Merchant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardQueryService
{
    public function forMerchant(Merchant $merchant): array
    {
        return Cache::remember(
            'dashboard:merchant:'.$merchant->id,
            60,
            fn (): array => $this->build($merchant),
        );
    }

    private function build(Merchant $merchant): array
    {
        $today = Carbon::now('UTC')->startOfDay();
        $monthStart = $today->copy()->startOfMonth();
        $lastMonthStart = $monthStart->copy()->subMonth();
        $dayCount = $today->day;

        $subscriptions = DB::table('subscriptions as s')
            ->join('customers as c', 'c.id', '=', 's.customer_id')
            ->where('s.merchant_id', $merchant->id)
            ->where('s.status', 'active')
            ->get(['s.id', 's.customer_id', 's.current_period_start', 's.current_period_end']);

        $customerIds = $subscriptions->pluck('customer_id')->unique()->values();
        $aggregates = $customerIds->isEmpty()
            ? collect()
            : DB::table('usage_daily_aggregates')
                ->whereIn('customer_id', $customerIds)
                ->whereDate('usage_date', '>=', $lastMonthStart->toDateString())
                ->whereDate('usage_date', '<=', $today->toDateString())
                ->get(['customer_id', 'usage_date', 'units_total'])
                ->map(function ($row) {
                    $row->usage_date = substr((string) $row->usage_date, 0, 10);
                    return $row;
                });

        $segments = $subscriptions->isEmpty()
            ? collect()
            : DB::table('subscription_segments as ss')
                ->join('plans as p', 'p.id', '=', 'ss.plan_id')
                ->whereIn('ss.subscription_id', $subscriptions->pluck('id'))
                ->get([
                    'ss.subscription_id', 'ss.plan_id', 'ss.starts_at', 'ss.ends_at',
                    'p.name', 'p.included_units', 'p.overage_rate_paise_per_unit',
                ]);

        $currentUsage = 0;
        $currentAllowance = 0;
        $activePlanName = null;
        $topCustomers = [];
        $projection = 0;

        foreach ($subscriptions as $subscription) {
            $customerAggregates = $aggregates->where('customer_id', $subscription->customer_id);
            $currentStart = Carbon::parse($subscription->current_period_start, 'UTC')->startOfDay();
            $currentEnd = Carbon::parse($subscription->current_period_end, 'UTC')->startOfDay();
            $periodUsage = (int) $customerAggregates
                ->whereBetween('usage_date', [$currentStart->toDateString(), $today->toDateString()])
                ->sum('units_total');
            $periodDays = $currentStart->diffInDays($currentEnd) + 1;
            $elapsedDays = min($currentStart->diffInDays($today) + 1, $periodDays);

            foreach ($segments->where('subscription_id', $subscription->id) as $segment) {
                $start = max($currentStart, Carbon::parse($segment->starts_at, 'UTC')->startOfDay());
                $end = min($currentEnd, $segment->ends_at
                    ? Carbon::parse($segment->ends_at, 'UTC')->startOfDay()
                    : $currentEnd);
                if ($activePlanName === null && $start <= $today && $today <= $end) {
                    $activePlanName = $segment->name;
                }
                $segmentDays = max(0, $start->diffInDays($end) + 1);
                $currentAllowance += intdiv(((int) $segment->included_units * $segmentDays * 2) + $periodDays, $periodDays * 2);
            }

            $currentUsage += $periodUsage;
            $monthUsage = (int) $customerAggregates
                ->whereBetween('usage_date', [$monthStart->toDateString(), $today->toDateString()])
                ->sum('units_total');
            $allowance = max(1, (int) $segments->where('subscription_id', $subscription->id)->sum('included_units'));
            $topCustomers[$subscription->customer_id] = [
                'customer_id' => $subscription->customer_id,
                'usage_units' => $monthUsage,
                'allowance_units' => $allowance,
                'allowance_used_percent' => round(($monthUsage / $allowance) * 100, 2),
            ];

            $dailyRate = $elapsedDays > 0 ? $periodUsage / $elapsedDays : 0;
            $remainingDays = max(0, $periodDays - $elapsedDays);
            foreach ($segments->where('subscription_id', $subscription->id) as $segment) {
                $projection += (int) max(0, round($dailyRate * $remainingDays)) * (int) $segment->overage_rate_paise_per_unit;
            }
        }

        $dailyUsage = $aggregates
            ->groupBy('usage_date')
            ->map(fn ($rows): int => (int) $rows->sum('units_total'))
            ->sortKeys()
            ->map(fn (int $units, string $date): array => ['date' => $date, 'units' => $units])
            ->values()
            ->all();

        $currentByCustomer = $aggregates->whereBetween('usage_date', [$monthStart->toDateString(), $today->toDateString()])
            ->groupBy('customer_id')->map(fn ($rows): int => (int) $rows->sum('units_total'));
        $previousStart = $lastMonthStart;
        $previousEnd = $lastMonthStart->copy()->addDays($dayCount - 1);
        $previousByCustomer = $aggregates->whereBetween('usage_date', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->groupBy('customer_id')->map(fn ($rows): int => (int) $rows->sum('units_total'));
        $churnRisk = $currentByCustomer->filter(
            fn (int $usage, int $customerId): bool => $previousByCustomer->get($customerId, 0) > 0
                && $usage < ($previousByCustomer->get($customerId) * 0.5)
        )->keys()->values()->all();

        return [
            'current_cycle' => [
                'usage_units' => $currentUsage,
                'allowance_units' => $currentAllowance,
                'usage_percent' => $currentAllowance > 0 ? round($currentUsage / $currentAllowance * 100, 2) : 0,
            ],
            'active_plan_name' => $activePlanName,
            'top_customers' => collect($topCustomers)->sortByDesc('usage_units')->take(5)->values()->all(),
            'projected_overage_revenue_paise' => $projection,
            'churn_risk' => ['customer_ids' => $churnRisk, 'count' => count($churnRisk)],
            'daily_usage' => $dailyUsage,
        ];
    }
}
