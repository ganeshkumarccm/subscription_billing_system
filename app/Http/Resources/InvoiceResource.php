<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'customer_name' => $this->subscription?->customer?->name,
            'customer_email' => $this->subscription?->customer?->email,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'subtotal' => $this->subtotal,
            'status' => $this->status,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
