<?php

use App\DTOs\BillingPlanSnapshot;
use App\DTOs\BillingSegmentData;
use App\Services\InvoiceCalculator;

function billingPlan(int $id = 1, int $price = 3100, int $included = 100, int $rate = 10): BillingPlanSnapshot
{
    return new BillingPlanSnapshot($id, $price, $included, $rate);
}

function billingSegment(BillingPlanSnapshot $plan, string $start, ?string $end, int $usage = 0): BillingSegmentData
{
    return new BillingSegmentData($plan, new DateTimeImmutable($start), $end ? new DateTimeImmutable($end) : null, $usage);
}

it('calculates a full cycle with no overage', function () {
    $invoice = (new InvoiceCalculator())->calculate(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-01-31'), [
        billingSegment(billingPlan(price: 10000, included: 100), '2026-01-01', null, 100),
    ]);

    expect($invoice->items[0]->amountPaise)->toBe(10000)
        ->and($invoice->items[1]->amountPaise)->toBe(0)
        ->and($invoice->subtotalPaise)->toBe(10000);
});

it('handles allowance equality, one unit over, and zero usage', function () {
    $calculator = new InvoiceCalculator();
    $plan = billingPlan(price: 1000, included: 100, rate: 7);
    $equal = $calculator->calculate(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-01-31'), [billingSegment($plan, '2026-01-01', null, 100)]);
    $over = $calculator->calculate(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-01-31'), [billingSegment($plan, '2026-01-01', null, 101)]);
    $zero = $calculator->calculate(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-01-31'), [billingSegment($plan, '2026-01-01', null, 0)]);

    expect($equal->items[1]->amountPaise)->toBe(0)
        ->and($over->items[1]->quantity)->toBe(1)
        ->and($over->items[1]->amountPaise)->toBe(7)
        ->and($zero->subtotalPaise)->toBe(1000);
});

it('supports mid-cycle upgrades and downgrades with two changes', function () {
    $invoice = (new InvoiceCalculator())->calculate(new DateTimeImmutable('2026-01-01'), new DateTimeImmutable('2026-01-31'), [
        billingSegment(billingPlan(1, 10000), '2026-01-10', '2026-01-19'),
        billingSegment(billingPlan(2, 20000), '2026-01-20', '2026-01-24'),
        billingSegment(billingPlan(3, 5000), '2026-01-25', null),
    ]);

    expect($invoice->items)->toHaveCount(6)
        ->and($invoice->subtotalPaise)->toBeGreaterThan(0);
});

it('handles changes on the first and last day', function () {
    $invoice = (new InvoiceCalculator())->calculate(new DateTimeImmutable('2026-03-01'), new DateTimeImmutable('2026-03-31'), [
        billingSegment(billingPlan(1, 10000), '2026-03-01', '2026-03-30'),
        billingSegment(billingPlan(2, 20000), '2026-03-31', null),
    ]);

    expect($invoice->items[0]->segmentDays)->toBe(30)
        ->and($invoice->items[2]->segmentDays)->toBe(1);
});

it('handles 28, 29, 30 and 31 day cycles including leap year', function (string $start, string $end, int $days) {
    $invoice = (new InvoiceCalculator())->calculate(new DateTimeImmutable($start), new DateTimeImmutable($end), [
        billingSegment(billingPlan(price: 10000), $start, null),
    ]);

    expect($invoice->items[0]->segmentDays)->toBe($days)
        ->and($invoice->subtotalPaise)->toBe(10000);
})->with([
    ['2026-02-01', '2026-02-28', 28],
    ['2024-02-01', '2024-02-29', 29],
    ['2026-04-01', '2026-04-30', 30],
    ['2026-01-01', '2026-01-31', 31],
]);

it('places rounding remainder so line items sum exactly to the total', function () {
    $invoice = (new InvoiceCalculator())->calculate(new DateTimeImmutable('2026-02-01'), new DateTimeImmutable('2026-02-03'), [
        billingSegment(billingPlan(1, 100), '2026-02-01', '2026-02-01'),
        billingSegment(billingPlan(2, 101), '2026-02-02', null),
    ]);

    $baseTotal = collect($invoice->items)->where('type', 'base')->sum('amountPaise');
    expect($baseTotal)->toBe(101)->and($invoice->subtotalPaise)->toBe($baseTotal);
});
