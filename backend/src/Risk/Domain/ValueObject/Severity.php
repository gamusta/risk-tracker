<?php

declare(strict_types=1);

namespace App\Risk\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * Value Object: Gravité risque (1-5)
 */
#[ORM\Embeddable]
final class Severity
{
    private const MIN_VALUE = 1;
    private const MAX_VALUE = 5;

    #[ORM\Column(type: 'integer', name: 'severity')]
    private int $value;

    public function __construct(?int $value = null)
    {
        if ($value === null) {
            return; // Doctrine hydration
        }

        if ($value < self::MIN_VALUE || $value > self::MAX_VALUE) {
            throw new InvalidArgumentException(
                sprintf('Severity must be between %d and %d, got %d', self::MIN_VALUE, self::MAX_VALUE, $value)
            );
        }
        $this->value = $value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public static function low(): self
    {
        return new self(1);
    }

    public static function medium(): self
    {
        return new self(3);
    }

    public static function high(): self
    {
        return new self(5);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}