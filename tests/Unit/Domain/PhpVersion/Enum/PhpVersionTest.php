<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Tests\Unit\Domain\PhpVersion\Enum;

use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use PHPUnit\Framework\TestCase;

final class PhpVersionTest extends TestCase
{
    public function testEnumHasExpectedCases(): void
    {
        $cases = PhpVersion::cases();

        self::assertCount(12, $cases);
        self::assertContains(PhpVersion::PHP_5_6, $cases);
        self::assertContains(PhpVersion::PHP_7_0, $cases);
        self::assertContains(PhpVersion::PHP_7_1, $cases);
        self::assertContains(PhpVersion::PHP_7_2, $cases);
        self::assertContains(PhpVersion::PHP_7_3, $cases);
        self::assertContains(PhpVersion::PHP_7_4, $cases);
        self::assertContains(PhpVersion::PHP_8_0, $cases);
        self::assertContains(PhpVersion::PHP_8_1, $cases);
        self::assertContains(PhpVersion::PHP_8_2, $cases);
        self::assertContains(PhpVersion::PHP_8_3, $cases);
        self::assertContains(PhpVersion::PHP_8_4, $cases);
        self::assertContains(PhpVersion::PHP_8_5, $cases);
    }

    public function testEnumValuesAreCorrect(): void
    {
        self::assertSame('php56', PhpVersion::PHP_5_6->value);
        self::assertSame('php70', PhpVersion::PHP_7_0->value);
        self::assertSame('php71', PhpVersion::PHP_7_1->value);
        self::assertSame('php72', PhpVersion::PHP_7_2->value);
        self::assertSame('php73', PhpVersion::PHP_7_3->value);
        self::assertSame('php74', PhpVersion::PHP_7_4->value);
        self::assertSame('php80', PhpVersion::PHP_8_0->value);
        self::assertSame('php81', PhpVersion::PHP_8_1->value);
        self::assertSame('php82', PhpVersion::PHP_8_2->value);
        self::assertSame('php83', PhpVersion::PHP_8_3->value);
        self::assertSame('php84', PhpVersion::PHP_8_4->value);
        self::assertSame('php85', PhpVersion::PHP_8_5->value);
    }

    public function testFromValueCreatesCorrectEnum(): void
    {
        self::assertSame(PhpVersion::PHP_8_4, PhpVersion::from('php84'));
        self::assertSame(PhpVersion::PHP_8_3, PhpVersion::from('php83'));
        self::assertSame(PhpVersion::PHP_7_4, PhpVersion::from('php74'));
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        self::assertNull(PhpVersion::tryFrom('php99'));
        self::assertNull(PhpVersion::tryFrom('invalid'));
        self::assertNull(PhpVersion::tryFrom(''));
    }

    public function testTryFromReturnsEnumForValidValue(): void
    {
        self::assertSame(PhpVersion::PHP_8_4, PhpVersion::tryFrom('php84'));
        self::assertSame(PhpVersion::PHP_5_6, PhpVersion::tryFrom('php56'));
    }

    public function testEnumIsBackedByString(): void
    {
        $enum = PhpVersion::PHP_8_4;

        self::assertIsString($enum->value);
    }

    public function testEnumCanBeComparedInConditionals(): void
    {
        $version = PhpVersion::PHP_8_4;

        $isModern = match (true) {
            $version === PhpVersion::PHP_8_3 || $version === PhpVersion::PHP_8_4 => true,
            default => false,
        };

        self::assertTrue($isModern);
    }

    public function testEnumCasesAreSingleton(): void
    {
        $version1 = PhpVersion::PHP_8_4;
        $version2 = PhpVersion::from('php84');

        self::assertSame($version1, $version2);
    }
}
