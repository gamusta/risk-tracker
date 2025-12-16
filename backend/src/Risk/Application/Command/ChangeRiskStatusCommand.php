<?php

declare(strict_types=1);

namespace App\Risk\Application\Command;

use App\Risk\Domain\ValueObject\RiskStatus;

/**
 * Command: Changer statut risque
 */
final readonly class ChangeRiskStatusCommand
{
    public function __construct(
        public int $riskId,
        public RiskStatus $newStatus
    ) {
    }
}
