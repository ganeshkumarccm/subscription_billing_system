<?php

namespace App\DTOs;

use App\Models\Merchant;

final readonly class CreateSubscriptionData
{
    public function __construct(
        public Merchant $merchant,
        public int $customerId,
        public int $planId,
    ) {
    }
}
