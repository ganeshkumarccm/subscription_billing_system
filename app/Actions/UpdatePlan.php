<?php

namespace App\Actions;

use App\DTOs\PlanData;
use App\Models\Plan;
use Illuminate\Validation\ValidationException;

final class UpdatePlan
{
    public function execute(PlanData $data, int $planId): Plan
    {
        $plan = $data->merchant->plans()->find($planId);

        if (! $plan) {
            throw ValidationException::withMessages([
                'plan' => 'The plan does not belong to this merchant.',
            ]);
        }

        $plan->update([
            'name' => $data->name,
            'base_price_paise' => $data->basePricePaise,
            'billing_cycle' => $data->billingCycle,
            'included_units' => $data->includedUnits,
            'overage_rate_paise_per_unit' => $data->overageRatePaisePerUnit,
            'is_active' => $data->isActive,
        ]);

        return $plan->refresh();
    }
}
