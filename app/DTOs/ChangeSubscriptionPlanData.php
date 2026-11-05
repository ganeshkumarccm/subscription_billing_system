<?php

namespace App\DTOs;

use App\Models\Merchant;
use Illuminate\Support\Carbon;

final readonly class ChangeSubscriptionPlanData
{
    public function __construct(
        public Merchant $merchant,
        public int $subscriptionId,
        public int $planId,
        public Carbon $changeAt,
    ) {
    }
}
