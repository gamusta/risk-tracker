<?php

declare(strict_types=1);

namespace App\Risk\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Value Object: Statut workflow risque
 */
enum RiskStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case ASSESSED = 'assessed';
    case MITIGATED = 'mitigated';
    case CLOSED = 'closed';

    /**
     * Transitions autorisées par statut (State Pattern)
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::OPEN],
            self::OPEN => [self::ASSESSED, self::CLOSED],
            self::ASSESSED => [self::MITIGATED, self::CLOSED],
            self::MITIGATED => [self::CLOSED],
            self::CLOSED => [],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }

    public function isClosed(): bool
    {
        return $this === self::CLOSED;
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }
}