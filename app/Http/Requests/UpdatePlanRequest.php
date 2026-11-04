<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdatePlanRequest extends StorePlanRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('plans', 'name')
                    ->where('merchant_id', auth('api')->user()?->merchant_id)
                    ->ignore($this->route('id')),
            ],
        ];
    }
}
