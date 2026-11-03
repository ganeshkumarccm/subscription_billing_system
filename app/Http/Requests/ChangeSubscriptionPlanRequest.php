<?php

namespace App\Http\Requests;

use App\Models\Merchant;
use Illuminate\Foundation\Http\FormRequest;

class ChangeSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('merchant');
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
        return $this->attributes->get('merchant');
    }
}
