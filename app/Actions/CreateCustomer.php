<?php

namespace App\Actions;

use App\DTOs\CustomerData;
use App\Models\Customer;

final class CreateCustomer
{
    public function execute(CustomerData $data): Customer
    {
        return $data->merchant->customers()->create([
            'name' => $data->name,
            'email' => $data->email,
        ]);
    }
}
