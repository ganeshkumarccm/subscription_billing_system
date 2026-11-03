<?php

namespace App\Services;

use App\DTOs\BillingSegmentData;
use App\DTOs\InvoiceCalculationData;
use App\DTOs\InvoiceLineItemData;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class InvoiceCalculator
{
    /**
     * @param array<int, BillingSegmentData> $segments
     */
    public function calculate(
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd,
        array $segments,
    ): InvoiceCalculationData {
        $periodStart = $this->day($periodStart);
        $periodEnd = $this->day($periodEnd);
        $cycleDays = $this->daysInclusive($periodStart, $periodEnd);

        if ($cycleDays < 1) {
            throw new InvalidArgumentException('The billing period must contain at least one day.');
        }
        if ($segments === []) {
            throw new InvalidArgumentException('At least one billing segment is required.');
        }

        $rows = [];
        $baseNumeratorTotal = 0;
        $lastIndex = count($segments) - 1;

        foreach ($segments as $index => $segment) {
            $start = $this->laterDate($periodStart, $this->day($segment->startsAt));
            $end = $this->earlierDate($periodEnd, $this->day($segment->endsAt ?? $periodEnd));
            $days = $this->daysInclusive($start, $end);

            if ($days < 1) {
                throw new InvalidArgumentException('Billing segments must overlap the billing period.');
            }

            $baseNumerator = $segment->plan->basePricePaise * $days;
            $baseNumeratorTotal += $baseNumerator;
            $allowance = $this->roundHalfUp($segment->plan->includedUnits * $days, $cycleDays);
            $overageUnits = max(0, $segment->usageUnits - $allowance);
            $overageAmount = $overageUnits * $segment->plan->overageRatePaisePerUnit;

            $rows[] = [
                'index' => $index,
                'segment' => $segment,
                'days' => $days,
                'base_numerator' => $baseNumerator,
                'allowance' => $allowance,
                'overage_units' => $overageUnits,
                'overage_amount' => $overageAmount,
            ];
        }

        $totalBase = $this->roundHalfUp($baseNumeratorTotal, $cycleDays);
        $baseAllocated = 0;
        $items = [];

        foreach ($rows as $rowIndex => $row) {
            $segment = $row['segment'];
            $baseAmount = $rowIndex === count($rows) - 1
                ? $totalBase - $baseAllocated
                : $this->roundHalfUp($row['base_numerator'], $cycleDays);
            $baseAllocated += $baseAmount;

            $items[] = new InvoiceLineItemData(
                type: 'base',
                planId: $segment->plan->planId,
                description: 'Base subscription fee',
                quantity: $row['days'],
                unitPricePaise: $segment->plan->basePricePaise,
                amountPaise: $baseAmount,
                segmentDays: $row['days'],
                segmentStart: $this->laterDate($periodStart, $this->day($segment->startsAt)),
                segmentEnd: $this->earlierDate($periodEnd, $this->day($segment->endsAt ?? $periodEnd)),
            );
            $items[] = new InvoiceLineItemData(
                type: 'overage',
                planId: $segment->plan->planId,
                description: 'Usage overage',
                quantity: $row['overage_units'],
                unitPricePaise: $segment->plan->overageRatePaisePerUnit,
                amountPaise: $row['overage_amount'],
                segmentDays: $row['days'],
                segmentStart: $this->laterDate($periodStart, $this->day($segment->startsAt)),
                segmentEnd: $this->earlierDate($periodEnd, $this->day($segment->endsAt ?? $periodEnd)),
            );
        }

        $subtotal = array_sum(array_map(
            static fn (InvoiceLineItemData $item): int => $item->amountPaise,
            $items,
        ));

        return new InvoiceCalculationData($periodStart, $periodEnd, $items, $subtotal);
    }

    private function day(DateTimeInterface $date): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('!Y-m-d', $date->format('Y-m-d'));
    }

    private function daysInclusive(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        return (int) $start->diff($end)->format('%r%a') + 1;
    }

    private function laterDate(DateTimeImmutable $left, DateTimeImmutable $right): DateTimeImmutable
    {
        return $left > $right ? $left : $right;
    }

    private function earlierDate(DateTimeImmutable $left, DateTimeImmutable $right): DateTimeImmutable
    {
        return $left < $right ? $left : $right;
    }

    private function roundHalfUp(int $numerator, int $denominator): int
    {
        return intdiv(($numerator * 2) + $denominator, $denominator * 2);
    }
}
