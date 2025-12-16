<?php

declare(strict_types=1);

namespace App\Risk\Infrastructure\Service;

use App\Risk\Domain\Service\ScoreCalculatorInterface;
use App\Risk\Domain\ValueObject\Probability;
use App\Risk\Domain\ValueObject\RiskScore;
use App\Risk\Domain\ValueObject\Severity;

/**
 * Strategy: Matrice 5×5 avec poids personnalisés
 */
final class MatrixScoreCalculator implements ScoreCalculatorInterface
{
    /**
     * Matrice risque 5×5
     * Lignes = Severity (1-5)
     * Colonnes = Probability (1-5)
     */
    private const MATRIX = [
        [1,  2,  4,  7,  11],  // Severity 1 (very low)
        [3,  5,  8,  12, 16],  // Severity 2 (low)
        [6,  9,  13, 17, 21],  // Severity 3 (medium)
        [10, 14, 18, 22, 24],  // Severity 4 (high)
        [15, 19, 23, 25, 25],  // Severity 5 (critical)
    ];

    public function calculate(Severity $severity, Probability $probability): RiskScore
    {
        $severityIndex = $severity->value() - 1;
        $probabilityIndex = $probability->value() - 1;

        $score = self::MATRIX[$severityIndex][$probabilityIndex];

        return RiskScore::fromInt($score);
    }

    public function getName(): string
    {
        return 'matrix';
    }
}
