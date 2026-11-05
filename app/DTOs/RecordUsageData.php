<?php

namespace App\DTOs;

use App\Models\Merchant;
use Illuminate\Support\Carbon;

final readonly class RecordUsageData
{
    public function __construct(
        public Merchant $merchant,
        public int $customerId,
        public int $units,
        public string $idempotencyKey,
        public Carbon $occurredAt,
    ) {
    }
}
