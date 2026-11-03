<?php

namespace App\Http\Controllers;

use App\Actions\ChangeSubscriptionPlan;
use App\DTOs\ChangeSubscriptionPlanData;
use App\Http\Requests\ChangeSubscriptionPlanRequest;
use App\Http\Resources\SubscriptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class SubscriptionController extends Controller
{
    public function changePlan(
        ChangeSubscriptionPlanRequest $request,
        int $id,
        ChangeSubscriptionPlan $changeSubscriptionPlan,
    ): JsonResponse {
        $subscription = $changeSubscriptionPlan->execute(new ChangeSubscriptionPlanData(
            merchant: $request->merchant(),
            subscriptionId: $id,
            planId: $request->integer('plan_id'),
            changeAt: $request->filled('change_at')
                ? Carbon::parse((string) $request->input('change_at'), 'UTC')->utc()
                : Carbon::now('UTC'),
        ));

        return (new SubscriptionResource($subscription))->response()->setStatusCode(200);
    }
}
