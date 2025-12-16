<?php

declare(strict_types=1);

namespace App\Tests\Unit\Risk\Domain\ValueObject;

use App\Risk\Domain\ValueObject\RiskStatus;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire: RiskStatus Enum (State Pattern)
 */
final class RiskStatusTest extends TestCase
{
    public function test_draft_can_transition_to_open(): void
    {
        $status = RiskStatus::DRAFT;

        $this->assertTrue($status->canTransitionTo(RiskStatus::OPEN));
    }

    public function test_draft_cannot_transition_to_assessed(): void
    {
        $status = RiskStatus::DRAFT;

        $this->assertFalse($status->canTransitionTo(RiskStatus::ASSESSED));
    }

    public function test_open_can_transition_to_assessed(): void
    {
        $status = RiskStatus::OPEN;

        $this->assertTrue($status->canTransitionTo(RiskStatus::ASSESSED));
    }

    public function test_open_can_transition_to_closed_directly(): void
    {
        $status = RiskStatus::OPEN;

        $this->assertTrue($status->canTransitionTo(RiskStatus::CLOSED));
    }

    public function test_open_cannot_transition_to_mitigated(): void
    {
        $status = RiskStatus::OPEN;

        $this->assertFalse($status->canTransitionTo(RiskStatus::MITIGATED));
    }

    public function test_assessed_can_transition_to_mitigated(): void
    {
        $status = RiskStatus::ASSESSED;

        $this->assertTrue($status->canTransitionTo(RiskStatus::MITIGATED));
    }

    public function test_mitigated_can_transition_to_closed(): void
    {
        $status = RiskStatus::MITIGATED;

        $this->assertTrue($status->canTransitionTo(RiskStatus::CLOSED));
    }

    public function test_closed_cannot_transition_anywhere(): void
    {
        $status = RiskStatus::CLOSED;

        $this->assertEmpty($status->allowedTransitions());
        $this->assertFalse($status->canTransitionTo(RiskStatus::OPEN));
    }

    public function test_is_closed_returns_true_for_closed_status(): void
    {
        $status = RiskStatus::CLOSED;

        $this->assertTrue($status->isClosed());
    }

    public function test_is_closed_returns_false_for_other_statuses(): void
    {
        $this->assertFalse(RiskStatus::DRAFT->isClosed());
        $this->assertFalse(RiskStatus::OPEN->isClosed());
    }

    public function test_is_draft_returns_true_for_draft_status(): void
    {
        $status = RiskStatus::DRAFT;

        $this->assertTrue($status->isDraft());
    }
}
