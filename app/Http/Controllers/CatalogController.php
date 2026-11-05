<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerIndexRequest;
use App\Http\Requests\PlanIndexRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\PlanResource;
use App\Actions\CreatePlan;
use App\Actions\CreateCustomer;
use App\Actions\UpdateCustomer;
use App\Actions\UpdatePlan;
use App\DTOs\CustomerData;
use App\DTOs\PlanData;
use App\Models\Customer;
use App\Services\MerchantContextService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function storeCustomer(
        StoreCustomerRequest $request,
        MerchantContextService $context,
        CreateCustomer $action,
    ): CustomerResource {
        return new CustomerResource($action->execute($this->customerData($request, $context)));
    }

    public function updateCustomer(
        UpdateCustomerRequest $request,
        int $id,
        MerchantContextService $context,
        UpdateCustomer $action,
    ): CustomerResource {
        return new CustomerResource($action->execute($this->customerData($request, $context), $id));
    }

    public function storePlan(StorePlanRequest $request, MerchantContextService $context, CreatePlan $action): PlanResource
    {
        return new PlanResource($action->execute($this->planData($request, $context)));
    }

    public function updatePlan(
        UpdatePlanRequest $request,
        int $id,
        MerchantContextService $context,
        UpdatePlan $action,
    ): PlanResource {
        return new PlanResource($action->execute($this->planData($request, $context), $id));
    }

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
        $customer = $context->fromUser()->customers()->with([
            'subscriptions.segments.plan',
            'subscriptions.invoices.items',
            'subscriptions.customer',
        ])
            ->findOrFail($id);
        $customer->setAttribute('usage_30_days', $customer->dailyAggregates()
            ->where('usage_date', '>=', now('UTC')->subDays(29)->toDateString())
            ->orderBy('usage_date')->get(['usage_date', 'units_total', 'event_count']));

        return new CustomerResource($customer);
    }

    private function planData(StorePlanRequest $request, MerchantContextService $context): PlanData
    {
        return new PlanData(
            merchant: $context->fromUser(),
            name: $request->string('name')->toString(),
            basePricePaise: $request->integer('base_price_paise'),
            billingCycle: $request->string('billing_cycle')->toString(),
            includedUnits: $request->integer('included_units'),
            overageRatePaisePerUnit: $request->integer('overage_rate_paise_per_unit'),
            isActive: $request->boolean('is_active', true),
        );
    }

    private function customerData(StoreCustomerRequest $request, MerchantContextService $context): CustomerData
    {
        return new CustomerData(
            merchant: $context->fromUser(),
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
        );
    }
}
