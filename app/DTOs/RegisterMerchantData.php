<?php

namespace App\DTOs;

final readonly class RegisterMerchantData
{
    public function __construct(
        public string $merchantName,
        public string $userName,
        public string $email,
        public string $password,
    ) {
    }
}
