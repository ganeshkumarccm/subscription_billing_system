<?php

namespace Database\Factories;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchantApiKeyFactory extends Factory
{
    public function definition(): array
    {
        return ['merchant_id' => Merchant::factory(), 'key_hash' => hash('sha256', fake()->unique()->uuid()), 'revoked_at' => null];
    }
}
