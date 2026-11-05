<?php

namespace App\Http\Requests;

use App\Models\Merchant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('merchant');
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'min:1'],
            'units' => ['required', 'integer', 'min:1', 'max:1000000'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }

    public function idempotencyKey(): string
    {
        return (string) $this->header('Idempotency-Key');
    }

    protected function prepareForValidation(): void
    {
        if (! $this->hasHeader('Idempotency-Key')) {
            return;
        }

        $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $key = $this->idempotencyKey();

            if ($key === '' || strlen($key) > 191) {
                $validator->errors()->add('Idempotency-Key', 'The Idempotency-Key header is required and must be at most 191 characters.');
            }
        });
    }

    public function merchant(): Merchant
    {
        return $this->attributes->get('merchant');
    }
}
