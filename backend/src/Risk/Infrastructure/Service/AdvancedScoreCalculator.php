<?php

declare(strict_types=1);

namespace App\Risk\Infrastructure\Service;

use App\Risk\Domain\Service\ScoreCalculatorInterface;
use App\Risk\Domain\ValueObject\Probability;
use App\Risk\Domain\ValueObject\RiskScore;
use App\Risk\Domain\ValueObject\Severity;

/**
 * Strategy: Calcul avancé avec pondération exponentielle
 */
final class AdvancedScoreCalculator implements ScoreCalculatorInterface
{
    /**
     * Pondération exponentielle favorisant risques critiques
     * Score = (Severity² × Probability) + (Probability² × Severity) / 2
     */
    public function calculate(Severity $severity, Probability $probability): RiskScore
    {
        $s = $severity->value();
        $p = $probability->value();

        // Formule pondérée
        $score = (int) round(
            (($s * $s * $p) + ($p * $p * $s)) / 2
        );

        // Cap à 25 max
        $score = min($score, 25);

        return RiskScore::fromInt($score);
    }

    public function getName(): string
    {
        return 'advanced';
    }
}
