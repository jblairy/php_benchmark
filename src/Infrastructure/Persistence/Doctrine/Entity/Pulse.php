<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as Orm;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;

#[Orm\Entity]
class Pulse
{
    #[Orm\Id]
    #[Orm\GeneratedValue]
    #[Orm\Column(type: Types::INTEGER)]
    public private(set) int $id = 0;

    #[Orm\Column(type: Types::STRING)]
    public private(set) string $benchId = 'temp';

    #[Orm\Column(type: Types::STRING)]
    public private(set) string $name = '';

    #[Orm\Column(type: Types::STRING, enumType: PhpVersion::class)]
    public private(set) PhpVersion $phpVersion = PhpVersion::PHP_5_6;

    #[Orm\Column(type: Types::FLOAT)]
    public private(set) float $executionTimeMs = 0;

    #[Orm\Column(type: Types::FLOAT)]
    public private(set) float $memoryUsedBytes = 0;

    #[Orm\Column(type: Types::FLOAT)]
    public private(set) float $memoryPeakByte = 0;

    public static function create(
        float $executionTimeMs,
        float $memoryUsedBytes,
        float $memoryPeakBytes,
        PhpVersion $phpVersion,
        string $className,
    ): self {
        $pulse = new self();
        $pulse->executionTimeMs = $executionTimeMs;
        $pulse->memoryUsedBytes = $memoryUsedBytes;
        $pulse->memoryPeakByte = $memoryPeakBytes;
        $pulse->phpVersion = $phpVersion;
        $pulse->benchId = $className;
        $pulse->name = $className;

        return $pulse;
    }
}
