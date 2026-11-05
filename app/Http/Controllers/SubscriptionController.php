<?php

namespace App\Http\Controllers;

use App\Actions\ChangeSubscriptionPlan;
use App\Actions\CancelSubscription;
use App\Actions\CreateSubscription;
use App\DTOs\ChangeSubscriptionPlanData;
use App\DTOs\CreateSubscriptionData;
use App\Http\Requests\ChangeSubscriptionPlanRequest;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class SubscriptionController extends Controller
{
    public function cancel(
        int $merchant,
        int $id,
        CancelSubscription $cancelSubscription,
    ): JsonResponse {
        $subscription = $cancelSubscription->execute(
            auth('api')->user()->merchant,
            $id,
        );

        return (new SubscriptionResource($subscription))->response()->setStatusCode(200);
    }

    public function store(
        StoreSubscriptionRequest $request,
        int $customer,
        CreateSubscription $createSubscription,
    ): JsonResponse {
        $subscription = $createSubscription->execute(new CreateSubscriptionData(
            merchant: auth('api')->user()->merchant,
            customerId: $customer,
            planId: $request->integer('plan_id'),
        ));

        return (new SubscriptionResource($subscription))->response()->setStatusCode(201);
    }

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
