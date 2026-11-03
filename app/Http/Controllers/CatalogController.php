<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerIndexRequest;
use App\Http\Requests\PlanIndexRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\PlanResource;
use App\Models\Customer;
use App\Services\MerchantContextService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function plans(PlanIndexRequest $request, MerchantContextService $context)
    {
        return PlanResource::collection($context->fromUser()->plans()->paginate($request->integer('per_page', 15)));
    }

    public function customers(CustomerIndexRequest $request, MerchantContextService $context)
    {
        $query = $context->fromUser()->customers()->when($request->filled('search'), function ($query) use ($request): void {
            $search = '%'.$request->string('search')->toString().'%';
            $query->where(fn ($query) => $query->where('name', 'like', $search)->orWhere('email', 'like', $search));
        });

        return CustomerResource::collection($query->paginate($request->integer('per_page', 15)));
    }

    public function customer(int $id, MerchantContextService $context): CustomerResource
    {
        $customer = $context->fromUser()->customers()->with(['subscriptions.segments.plan', 'subscriptions.invoices.items'])
            ->findOrFail($id);
        $customer->setAttribute('usage_30_days', $customer->dailyAggregates()
            ->where('usage_date', '>=', now('UTC')->subDays(29)->toDateString())
            ->orderBy('usage_date')->get(['usage_date', 'units_total', 'event_count']));

        return new CustomerResource($customer);
    }
}
