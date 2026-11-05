<?php

namespace App\Http\Controllers;

use App\Actions\RecordUsageEvent;
use App\DTOs\RecordUsageData;
use App\Http\Requests\StoreUsageRequest;
use App\Http\Resources\UsageEventResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class UsageController extends Controller
{
    public function store(StoreUsageRequest $request, RecordUsageEvent $recordUsageEvent): JsonResponse
    {
        $result = $recordUsageEvent->execute(new RecordUsageData(
            merchant: $request->merchant(),
            customerId: $request->integer('customer_id'),
            units: $request->integer('units'),
            idempotencyKey: $request->idempotencyKey(),
            occurredAt: $request->filled('occurred_at')
                ? Carbon::parse((string) $request->input('occurred_at'), 'UTC')->utc()
                : Carbon::now('UTC'),
        ));

        return (new UsageEventResource($result['event']))
            ->additional(['created' => $result['created']])
            ->response()
            ->setStatusCode($result['created'] ? 201 : 200);
    }
}
