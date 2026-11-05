<?php

namespace App\Actions;

use App\DTOs\RegisterMerchantData;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class RegisterMerchantAction
{
    public function execute(RegisterMerchantData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $merchant = Merchant::create(['name' => $data->merchantName]);

            return User::create([
                'merchant_id' => $merchant->id,
                'name' => $data->userName,
                'email' => $data->email,
                'password' => $data->password,
            ]);
        });
    }
}
