<?php

namespace App\Http\Requests;

use App\Models\Merchant;
use Illuminate\Foundation\Http\FormRequest;

class ChangeSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->route('merchant') === (int) auth('api')->user()?->merchant_id;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'min:1'],
            'change_at' => ['nullable', 'date'],
        ];
    }

    public function merchant(): Merchant
    {
        return auth('api')->user()->merchant;
    }
}
