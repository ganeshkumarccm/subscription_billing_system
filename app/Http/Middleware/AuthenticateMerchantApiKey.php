<?php

namespace App\Http\Middleware;

use App\Models\MerchantApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMerchantApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextKey = $request->header('X-API-Key');

        if (! is_string($plainTextKey) || $plainTextKey === '') {
            return response()->json(['message' => 'Invalid API key.'], Response::HTTP_UNAUTHORIZED);
        }

        $keyHash = hash('sha256', $plainTextKey);
        $apiKey = MerchantApiKey::query()
            ->whereNull('revoked_at')
            ->where('key_hash', $keyHash)
            ->with('merchant')
            ->first();

        if (! $apiKey || ! hash_equals($apiKey->key_hash, $keyHash)) {
            return response()->json(['message' => 'Invalid API key.'], Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('merchant', $apiKey->merchant);
        $request->attributes->set('merchant_api_key', $apiKey);

        return $next($request);
    }
}
