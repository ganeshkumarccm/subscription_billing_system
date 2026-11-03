<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'email' => $this->email,
            'subscriptions' => SubscriptionResource::collection($this->whenLoaded('subscriptions')),
            'invoices' => InvoiceResource::collection(
                $this->when($this->relationLoaded('subscriptions'), $this->subscriptions->flatMap->invoices)
            ),
            'usage_30_days' => $this->when(isset($this->usage_30_days), $this->usage_30_days),
        ];
    }
}
