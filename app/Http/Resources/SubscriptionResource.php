<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'merchant_id' => $this->merchant_id,
            'status' => $this->status,
            'started_at' => $this->started_at?->toISOString(),
            'current_period_start' => $this->current_period_start?->toISOString(),
            'current_period_end' => $this->current_period_end?->toISOString(),
            'segments' => SubscriptionSegmentResource::collection($this->whenLoaded('segments')),
        ];
    }
}
