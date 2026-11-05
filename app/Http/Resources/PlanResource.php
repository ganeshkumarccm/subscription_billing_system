<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name,
            'base_price_paise' => $this->base_price_paise,
            'billing_cycle' => $this->billing_cycle,
            'included_units' => $this->included_units,
            'overage_rate_paise_per_unit' => $this->overage_rate_paise_per_unit,
            'is_active' => $this->is_active,
        ];
    }
}
