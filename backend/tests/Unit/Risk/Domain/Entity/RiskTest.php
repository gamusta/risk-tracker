<?php

declare(strict_types=1);

namespace App\Tests\Unit\Risk\Domain\Entity;

use App\Risk\Domain\Entity\Risk;
use App\Risk\Domain\ValueObject\Probability;
use App\Risk\Domain\ValueObject\RiskStatus;
use App\Risk\Domain\ValueObject\Severity;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire: Risk Entity (Domain behaviors)
 */
final class RiskTest extends TestCase
{
    public function test_creates_risk_with_valid_data(): void
    {
        $risk = Risk::create(
            title: 'Security Risk',
            type: 'security',
            severity: Severity::fromInt(4),
            probability: Probability::fromInt(3),
            description: 'Test description'
        );

        $this->assertInstanceOf(Risk::class, $risk);
        $this->assertEquals('Security Risk', $risk->getTitle());
        $this->assertEquals('security', $risk->getType());
        $this->assertEquals(4, $risk->getSeverity()->value());
        $this->assertEquals(3, $risk->getProbability()->value());
        $this->assertEquals(12, $risk->getScore()->value());
        $this->assertEquals(RiskStatus::DRAFT, $risk->getStatus());
    }

    public function test_calculates_score_automatically_on_creation(): void
    {
        $risk = Risk::create(
            title: 'Test Risk',
            type: 'cyber',
            severity: Severity::fromInt(5),
            probability: Probability::fromInt(5)
        );

        $this->assertEquals(25, $risk->getScore()->value());
        $this->assertEquals('critical', $risk->getScore()->level());
    }

    public function test_rejects_title_too_short(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Title must be at least 3 characters');

        Risk::create(
            title: 'AB',
            type: 'security',
            severity: Severity::fromInt(3),
            probability: Probability::fromInt(3)
        );
    }

    public function test_rejects_invalid_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Type must be one of: security, environment, social, cyber');

        Risk::create(
            title: 'Test Risk',
            type: 'invalid_type',
            severity: Severity::fromInt(3),
            probability: Probability::fromInt(3)
        );
    }

    public function test_changes_status_with_valid_transition(): void
    {
        $risk = Risk::create(
            title: 'Test Risk',
            type: 'security',
            severity: Severity::fromInt(3),
            probability: Probability::fromInt(3)
        );

        // draft → open
        $risk->changeStatus(RiskStatus::OPEN);

        $this->assertEquals(RiskStatus::OPEN, $risk->getStatus());
    }

    public function test_rejects_invalid_status_transition(): void
    {
        $risk = Risk::create(
            title: 'Test Risk',
            type: 'security',
            severity: Severity::fromInt(3),
            probability: Probability::fromInt(3)
        );

        // draft → open
        $risk->changeStatus(RiskStatus::OPEN);

        // open → mitigated (invalid)
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot transition from open to mitigated');

        $risk->changeStatus(RiskStatus::MITIGATED);
    }

    public function test_assess_updates_severity_probability_and_score(): void
    {
        $risk = Risk::create(
            title: 'Test Risk',
            type: 'security',
            severity: Severity::fromInt(2),
            probability: Probability::fromInt(2)
        );

        $initialScore = $risk->getScore()->value();
        $this->assertEquals(4, $initialScore);

        // Reassess with higher values
        $risk->assess(Severity::fromInt(5), Probability::fromInt(4));

        $this->assertEquals(5, $risk->getSeverity()->value());
        $this->assertEquals(4, $risk->getProbability()->value());
        $this->assertEquals(20, $risk->getScore()->value());
    }

    public function test_close_transitions_to_closed_status(): void
    {
        $risk = Risk::create(
            title: 'Test Risk',
            type: 'security',
            severity: Severity::fromInt(3),
            probability: Probability::fromInt(3)
        );

        $risk->changeStatus(RiskStatus::OPEN);
        $risk->close();

        $this->assertEquals(RiskStatus::CLOSED, $risk->getStatus());
        $this->assertTrue($risk->getStatus()->isClosed());
    }

    public function test_assign_to_site_sets_site_id(): void
    {
        $risk = Risk::create(
            title: 'Test Risk',
            type: 'security',
            severity: Severity::fromInt(3),
            probability: Probability::fromInt(3)
        );

        $risk->assignToSite(42);

        $this->assertEquals(42, $risk->getSiteId());
    }

    public function test_assign_to_user_sets_assigned_to_id(): void
    {
        $risk = Risk::create(
            title: 'Test Risk',
            type: 'security',
            severity: Severity::fromInt(3),
            probability: Probability::fromInt(3)
        );

        $risk->assignToUser(99);

        $this->assertEquals(99, $risk->getAssignedToId());
    }

    public function test_update_modifies_basic_fields(): void
    {
        $risk = Risk::create(
            title: 'Original Title',
            type: 'security',
            severity: Severity::fromInt(3),
            probability: Probability::fromInt(3),
            description: 'Original description'
        );

        $risk->update(
            title: 'Updated Title',
            type: 'cyber',
            description: 'Updated description'
        );

        $this->assertEquals('Updated Title', $risk->getTitle());
        $this->assertEquals('cyber', $risk->getType());
        $this->assertEquals('Updated description', $risk->getDescription());
    }
}
