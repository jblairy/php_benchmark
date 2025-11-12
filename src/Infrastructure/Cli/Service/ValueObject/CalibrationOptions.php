<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service\ValueObject;

/**
 * Value object encapsulating calibration command options.
 *
 * Replaces boolean flags with a cohesive configuration object.
 */
final readonly class CalibrationOptions
{
    public function __construct(
        public bool $isDryRun,
        public bool $forceRecalibration,
    ) {
    }

    public static function dryRun(): self
    {
        return new self(isDryRun: true, forceRecalibration: false);
    }

    public static function normal(): self
    {
        return new self(isDryRun: false, forceRecalibration: false);
    }

    public static function forced(): self
    {
        return new self(isDryRun: false, forceRecalibration: true);
    }

    public static function dryRunForced(): self
    {
        return new self(isDryRun: true, forceRecalibration: true);
    }

    public static function fromFlags(bool $dryRun, bool $force): self
    {
        return new self(isDryRun: $dryRun, forceRecalibration: $force);
    }

    public function shouldUpdateFixtures(): bool
    {
        return !$this->isDryRun;
    }

    public function shouldSkipConfigured(): bool
    {
        return !$this->forceRecalibration;
    }
}
