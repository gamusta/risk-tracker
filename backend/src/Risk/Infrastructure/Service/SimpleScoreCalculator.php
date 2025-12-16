<?php

declare(strict_types=1);

namespace App\Risk\Infrastructure\Service;

use App\Risk\Domain\Service\ScoreCalculatorInterface;
use App\Risk\Domain\ValueObject\Probability;
use App\Risk\Domain\ValueObject\RiskScore;
use App\Risk\Domain\ValueObject\Severity;

/**
 * Strategy: Calcul simple (Gravité × Probabilité)
 */
final class SimpleScoreCalculator implements ScoreCalculatorInterface
{
    public function calculate(Severity $severity, Probability $probability): RiskScore
    {
        $score = $severity->value() * $probability->value();
        return RiskScore::fromInt($score);
    }

    public function getName(): string
    {
        return 'simple';
    }
}
