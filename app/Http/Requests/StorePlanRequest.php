<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('plans', 'name')->where('merchant_id', auth('api')->user()?->merchant_id),
            ],
            'base_price_paise' => ['required', 'integer', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'included_units' => ['required', 'integer', 'min:0'],
            'overage_rate_paise_per_unit' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
