<?php

namespace App\Http\Controllers;

use App\Services\DashboardQueryService;
use App\Services\MerchantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request, DashboardQueryService $service, MerchantContextService $context): JsonResponse
    {
        $merchant = $request->route('merchant')
            ? $context->fromRoute($request)
            : $context->fromUser();

        return response()->json(['data' => $service->forMerchant($merchant)]);
    }
}
