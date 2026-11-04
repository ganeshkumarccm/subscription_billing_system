<?php

namespace App\Actions;

use App\DTOs\PlanData;
use App\Models\Plan;

final class CreatePlan
{
    public function execute(PlanData $data): Plan
    {
        return $data->merchant->plans()->create([
            'name' => $data->name,
            'base_price_paise' => $data->basePricePaise,
            'billing_cycle' => $data->billingCycle,
            'included_units' => $data->includedUnits,
            'overage_rate_paise_per_unit' => $data->overageRatePaisePerUnit,
            'is_active' => $data->isActive,
        ]);
    }
}
