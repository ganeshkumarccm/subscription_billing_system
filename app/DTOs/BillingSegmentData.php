<?php

namespace App\DTOs;

use DateTimeImmutable;
use DateTimeInterface;

final readonly class BillingSegmentData
{
    public DateTimeImmutable $startsAt;
    public ?DateTimeImmutable $endsAt;

    public function __construct(
        public BillingPlanSnapshot $plan,
        DateTimeInterface $startsAt,
        ?DateTimeInterface $endsAt,
        public int $usageUnits,
    ) {
        if ($usageUnits < 0) {
            throw new \InvalidArgumentException('Usage units cannot be negative.');
        }

        $this->startsAt = DateTimeImmutable::createFromInterface($startsAt);
        $this->endsAt = $endsAt === null ? null : DateTimeImmutable::createFromInterface($endsAt);
    }
}
