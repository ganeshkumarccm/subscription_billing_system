<?php

namespace App\DTOs;

use App\Models\Merchant;

final readonly class PlanData
{
    public function __construct(
        public Merchant $merchant,
        public string $name,
        public int $basePricePaise,
        public string $billingCycle,
        public int $includedUnits,
        public int $overageRatePaisePerUnit,
        public bool $isActive,
    ) {
    }
}
