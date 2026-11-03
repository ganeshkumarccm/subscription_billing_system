<?php

namespace App\DTOs;

use DateTimeImmutable;
use DateTimeInterface;

final readonly class InvoiceCalculationData
{
    public DateTimeImmutable $periodStart;
    public DateTimeImmutable $periodEnd;

    /**
     * @param array<int, InvoiceLineItemData> $items
     */
    public function __construct(
        DateTimeInterface $periodStart,
        DateTimeInterface $periodEnd,
        public array $items,
        public int $subtotalPaise,
    ) {
        $this->periodStart = DateTimeImmutable::createFromInterface($periodStart);
        $this->periodEnd = DateTimeImmutable::createFromInterface($periodEnd);
    }
}
