<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = Customer::query()->find((int) $this->route('customer'));

        return $customer !== null
            && (int) $customer->merchant_id === (int) auth('api')->user()?->merchant_id;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
