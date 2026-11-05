<?php

namespace App\Http\Controllers;

use App\Http\Resources\MerchantResource;
use App\Models\MerchantApiKey;
use App\Services\MerchantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    public function show(MerchantContextService $context): MerchantResource
    {
        return new MerchantResource($context->fromUser()->loadCount(['users', 'plans', 'customers', 'subscriptions'])->load('apiKeys'));
    }

    public function regenerateApiKey(MerchantContextService $context): JsonResponse
    {
        $merchant = $context->fromUser();
        $plain = Str::random(64);
        $merchant->apiKeys()->whereNull('revoked_at')->update(['revoked_at' => now('UTC')]);
        MerchantApiKey::create(['merchant_id' => $merchant->id, 'key_hash' => hash('sha256', $plain)]);

        return response()->json(['api_key' => $plain, 'message' => 'Store this API key; it will not be shown again.']);
    }
}
