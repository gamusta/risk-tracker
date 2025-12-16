<?php

declare(strict_types=1);

namespace App\Risk\Domain\Service;

use App\Risk\Domain\ValueObject\Probability;
use App\Risk\Domain\ValueObject\RiskScore;
use App\Risk\Domain\ValueObject\Severity;

/**
 * Strategy Pattern: Interface calcul score risque
 */
interface ScoreCalculatorInterface
{
    public function calculate(Severity $severity, Probability $probability): RiskScore;

    public function getName(): string;
}
