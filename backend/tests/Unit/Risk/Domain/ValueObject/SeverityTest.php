<?php

declare(strict_types=1);

namespace App\Tests\Unit\Risk\Domain\ValueObject;

use App\Risk\Domain\ValueObject\Severity;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire: Severity ValueObject
 */
final class SeverityTest extends TestCase
{
    public function test_creates_valid_severity(): void
    {
        $severity = Severity::fromInt(3);

        $this->assertInstanceOf(Severity::class, $severity);
        $this->assertEquals(3, $severity->value());
    }

    public function test_creates_low_severity(): void
    {
        $severity = Severity::low();

        $this->assertEquals(1, $severity->value());
    }

    public function test_creates_medium_severity(): void
    {
        $severity = Severity::medium();

        $this->assertEquals(3, $severity->value());
    }

    public function test_creates_high_severity(): void
    {
        $severity = Severity::high();

        $this->assertEquals(5, $severity->value());
    }

    public function test_rejects_value_below_minimum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Severity must be between 1 and 5, got 0');

        Severity::fromInt(0);
    }

    public function test_rejects_value_above_maximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Severity must be between 1 and 5, got 6');

        Severity::fromInt(6);
    }

    public function test_equals_compares_values(): void
    {
        $severity1 = Severity::fromInt(3);
        $severity2 = Severity::fromInt(3);
        $severity3 = Severity::fromInt(4);

        $this->assertTrue($severity1->equals($severity2));
        $this->assertFalse($severity1->equals($severity3));
    }
}
