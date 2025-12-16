<?php

declare(strict_types=1);

namespace App\Risk\Domain\Event;

use App\Risk\Domain\ValueObject\RiskStatus;
use DateTimeImmutable;

/**
 * Domain Event: Statut risque changé (Observer Pattern)
 */
final readonly class RiskStatusChanged
{
    public function __construct(
        public int $riskId,
        public RiskStatus $oldStatus,
        public RiskStatus $newStatus,
        public DateTimeImmutable $occurredAt
    ) {
    }
}
