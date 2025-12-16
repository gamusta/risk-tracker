<?php

declare(strict_types=1);

namespace App\Risk\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

/**
 * Value Object: Score risque calculé
 */
#[ORM\Embeddable]
final class RiskScore
{
    #[ORM\Column(type: 'integer', nullable: true, name: 'score')]
    private int $value;

    public function __construct(?int $value = null)
    {
        if ($value !== null) {
            $this->value = $value;
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public static function calculate(Severity $severity, Probability $probability): self
    {
        // Calcul simple: Gravité × Probabilité
        return new self($severity->value() * $probability->value());
    }

    public function value(): int
    {
        return $this->value;
    }

    public function isCritical(): bool
    {
        return $this->value >= 25;  // Only 5×5
    }

    public function isHigh(): bool
    {
        return $this->value >= 16 && $this->value < 25;
    }

    public function isMedium(): bool
    {
        return $this->value >= 8 && $this->value < 16;
    }

    public function isLow(): bool
    {
        return $this->value < 8;
    }

    public function level(): string
    {
        return match (true) {
            $this->isCritical() => 'critical',
            $this->isHigh() => 'high',
            $this->isMedium() => 'medium',
            default => 'low',
        };
    }
}
