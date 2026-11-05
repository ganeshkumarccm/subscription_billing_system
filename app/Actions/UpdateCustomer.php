<?php

namespace App\Actions;

use App\DTOs\CustomerData;
use App\Models\Customer;
use Illuminate\Validation\ValidationException;

final class UpdateCustomer
{
    public function execute(CustomerData $data, int $customerId): Customer
    {
        $customer = $data->merchant->customers()->find($customerId);

        if (! $customer) {
            throw ValidationException::withMessages([
                'customer' => 'The customer does not belong to this merchant.',
            ]);
        }

        $customer->update([
            'name' => $data->name,
            'email' => $data->email,
        ]);

        return $customer->refresh();
    }
}
