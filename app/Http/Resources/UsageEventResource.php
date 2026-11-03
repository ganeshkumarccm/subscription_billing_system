<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsageEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'customer_id' => $this->customer_id,
            'subscription_id' => $this->subscription_id,
            'idempotency_key' => $this->idempotency_key,
            'units' => $this->units,
            'usage_date' => $this->usage_date?->toDateString(),
            'occurred_at' => $this->occurred_at?->toISOString(),
        ];
    }
}
