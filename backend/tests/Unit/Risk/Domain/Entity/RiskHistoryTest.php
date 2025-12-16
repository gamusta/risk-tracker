<?php

declare(strict_types=1);

namespace App\Tests\Unit\Risk\Domain\Entity;

use App\Risk\Domain\Entity\RiskHistory;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire: RiskHistory Entity (Memento Pattern)
 */
final class RiskHistoryTest extends TestCase
{
    public function test_creates_history_record(): void
    {
        $history = RiskHistory::record(
            riskId: 42,
            action: 'status_changed',
            changes: [
                'old_status' => 'draft',
                'new_status' => 'open',
            ],
            userId: 99
        );

        $this->assertInstanceOf(RiskHistory::class, $history);
        $this->assertEquals(42, $history->getRiskId());
        $this->assertEquals('status_changed', $history->getAction());
        $this->assertEquals(['old_status' => 'draft', 'new_status' => 'open'], $history->getChanges());
        $this->assertEquals(99, $history->getUserId());
        $this->assertNotNull($history->getCreatedAt());
    }

    public function test_creates_history_without_user(): void
    {
        $history = RiskHistory::record(
            riskId: 42,
            action: 'created',
            changes: null,
            userId: null
        );

        $this->assertNull($history->getUserId());
        $this->assertNull($history->getChanges());
    }

    public function test_records_different_action_types(): void
    {
        $actions = ['created', 'updated', 'status_changed', 'assessed', 'closed'];

        foreach ($actions as $action) {
            $history = RiskHistory::record(
                riskId: 1,
                action: $action
            );

            $this->assertEquals($action, $history->getAction());
        }
    }
}
