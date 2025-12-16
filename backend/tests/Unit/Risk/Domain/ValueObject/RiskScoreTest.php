<?php

declare(strict_types=1);

namespace App\Tests\Unit\Risk\Domain\ValueObject;

use App\Risk\Domain\ValueObject\Probability;
use App\Risk\Domain\ValueObject\RiskScore;
use App\Risk\Domain\ValueObject\Severity;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire: RiskScore ValueObject
 */
final class RiskScoreTest extends TestCase
{
    public function test_calculates_score_from_severity_and_probability(): void
    {
        $severity = Severity::fromInt(5);
        $probability = Probability::fromInt(4);

        $score = RiskScore::calculate($severity, $probability);

        $this->assertEquals(20, $score->value());
    }

    public function test_calculates_maximum_score(): void
    {
        $severity = Severity::fromInt(5);
        $probability = Probability::fromInt(5);

        $score = RiskScore::calculate($severity, $probability);

        $this->assertEquals(25, $score->value());
    }

    public function test_calculates_minimum_score(): void
    {
        $severity = Severity::fromInt(1);
        $probability = Probability::fromInt(1);

        $score = RiskScore::calculate($severity, $probability);

        $this->assertEquals(1, $score->value());
    }

    public function test_identifies_critical_risk(): void
    {
        $score = RiskScore::fromInt(25);

        $this->assertTrue($score->isCritical());
        $this->assertEquals('critical', $score->level());
    }

    public function test_identifies_high_risk(): void
    {
        $score = RiskScore::fromInt(16);

        $this->assertTrue($score->isHigh());
        $this->assertFalse($score->isCritical());
        $this->assertEquals('high', $score->level());
    }

    public function test_identifies_medium_risk(): void
    {
        $score = RiskScore::fromInt(10);

        $this->assertTrue($score->isMedium());
        $this->assertEquals('medium', $score->level());
    }

    public function test_identifies_low_risk(): void
    {
        $score = RiskScore::fromInt(5);

        $this->assertTrue($score->isLow());
        $this->assertEquals('low', $score->level());
    }
}
