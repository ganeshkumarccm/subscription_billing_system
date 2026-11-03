<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $key = $this->apiKeys->first(fn ($apiKey) => $apiKey->revoked_at === null);
        return [
            'id' => $this->id,
            'name' => $this->name,
            'masked_api_key' => $key ? '********'.substr($key->key_hash, -4) : null,
            'counts' => [
                'users' => $this->users_count,
                'plans' => $this->plans_count,
                'customers' => $this->customers_count,
                'subscriptions' => $this->subscriptions_count,
            ],
        ];
    }
}
