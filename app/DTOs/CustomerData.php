<?php

namespace App\DTOs;

use App\Models\Merchant;

final readonly class CustomerData
{
    public function __construct(
        public Merchant $merchant,
        public string $name,
        public string $email,
    ) {
    }
}
