<?php

namespace App\DTOs;

final readonly class BillingPlanSnapshot
{
    public function __construct(
        public int $planId,
        public int $basePricePaise,
        public int $includedUnits,
        public int $overageRatePaisePerUnit,
    ) {
        if ($basePricePaise < 0 || $includedUnits < 0 || $overageRatePaisePerUnit < 0) {
            throw new \InvalidArgumentException('Billing plan amounts and units cannot be negative.');
        }
    }
}
