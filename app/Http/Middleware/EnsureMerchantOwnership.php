<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMerchantOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $merchantId = $request->route('merchant');
        $user = auth('api')->user();

        if ((string) $merchantId !== (string) $user?->merchant_id) {
            return response()->json(['message' => 'You do not own this merchant.'], 403);
        }

        return $next($request);
    }
}
