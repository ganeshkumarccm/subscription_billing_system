<?php

namespace App\DTOs;

use DateTimeImmutable;

final readonly class InvoiceLineItemData
{
    public function __construct(
        public string $type,
        public int $planId,
        public string $description,
        public int $quantity,
        public int $unitPricePaise,
        public int $amountPaise,
        public int $segmentDays,
        public DateTimeImmutable $segmentStart,
        public DateTimeImmutable $segmentEnd,
    ) {
    }
}
