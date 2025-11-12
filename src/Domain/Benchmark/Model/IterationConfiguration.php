<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\Benchmark\Model;

use InvalidArgumentException;

/**
 * Value Object for benchmark iteration configuration.
 * Provides smart defaults based on benchmark characteristics.
 */
final readonly class IterationConfiguration
{
    private const int DEFAULT_WARMUP_ITERATIONS = 10;

    private const int DEFAULT_INNER_ITERATIONS = 100;

    private const int MIN_WARMUP_ITERATIONS = 1;

    private const int MIN_INNER_ITERATIONS = 10;

    private const int MAX_WARMUP_ITERATIONS = 100;

    private const int MAX_INNER_ITERATIONS = 10000;

    public function __construct(
        public int $warmupIterations,
        public int $innerIterations,
    ) {
        $this->validate();
    }

    /**
     * Create from nullable values with smart defaults.
     *
     * @param int|null    $warmupIterations Explicit warmup iterations
     * @param int|null    $innerIterations  Explicit inner iterations
     * @param string|null $benchmarkCode    Code to analyze for complexity-based defaults
     * @param int         $defaultWarmup    Default warmup when no other source available
     * @param int         $defaultInner     Default inner when no other source available
     */
    public static function createWithDefaults(
        ?int $warmupIterations = null,
        ?int $innerIterations = null,
        ?string $benchmarkCode = null,
        int $defaultWarmup = self::DEFAULT_WARMUP_ITERATIONS,
        int $defaultInner = self::DEFAULT_INNER_ITERATIONS,
    ): self {
        if (self::hasExplicitValues($warmupIterations, $innerIterations)) {
            return new self(
                $warmupIterations ?? self::DEFAULT_WARMUP_ITERATIONS,
                $innerIterations ?? self::DEFAULT_INNER_ITERATIONS,
            );
        }

        if (self::canCalculateFromCode($benchmarkCode)) {
            return self::createFromCodeComplexity($benchmarkCode, $warmupIterations, $innerIterations, $defaultWarmup, $defaultInner);
        }

        return new self(
            $warmupIterations ?? $defaultWarmup,
            $innerIterations ?? $defaultInner,
        );
    }

    public function getTotalMeasurementIterations(): int
    {
        return $this->innerIterations;
    }

    public function getDescription(): string
    {
        return sprintf(
            'Warmup: %d, Inner: %d (Total: %d measurements)',
            $this->warmupIterations,
            $this->innerIterations,
            $this->innerIterations,
        );
    }

    private static function hasExplicitValues(?int $warmupIterations, ?int $innerIterations): bool
    {
        return null !== $warmupIterations && null !== $innerIterations;
    }

    private static function canCalculateFromCode(?string $benchmarkCode): bool
    {
        return null !== $benchmarkCode;
    }

    private static function createFromCodeComplexity(
        ?string $benchmarkCode,
        ?int $warmupIterations,
        ?int $innerIterations,
        int $defaultWarmup,
        int $defaultInner,
    ): self {
        if (null === $benchmarkCode) {
            return new self(
                $warmupIterations ?? $defaultWarmup,
                $innerIterations ?? $defaultInner,
            );
        }

        $complexity = self::analyzeBenchmarkComplexity($benchmarkCode);

        return new self(
            $warmupIterations ?? self::calculateWarmupIterations($complexity),
            $innerIterations ?? self::calculateInnerIterations($complexity),
        );
    }

    /**
     * Analyze benchmark code complexity.
     *
     * @return array{level: string, score: float, estimatedOperations: int}
     */
    private static function analyzeBenchmarkComplexity(string $code): array
    {
        $loopMatches = [];
        preg_match_all('/for\s*\([^;]+;\s*(\d+)\s*>/', $code, $loopMatches);

        $totalIterations = 1;
        $matches = $loopMatches[1];
        foreach ($matches as $match) {
            $totalIterations *= (int) $match;
        }

        $heavyOperations = [
            'mb_' => 2.0,
            'preg_' => 3.0,
            'hash(' => 3.0,
            'crypt(' => 5.0,
            'serialize(' => 2.5,
            'json_encode(' => 2.0,
            'json_decode(' => 2.5,
            'file_get_contents(' => 10.0,
            'curl_' => 10.0,
        ];

        $operationScore = 1.0;
        foreach ($heavyOperations as $operation => $weight) {
            if (str_contains($code, $operation)) {
                $operationScore = max($operationScore, $weight);
            }
        }

        $score = log10($totalIterations + 1) * $operationScore;

        return [
            'level' => match (true) {
                15 <= $score => 'extreme',
                10 <= $score => 'heavy',
                5 <= $score => 'moderate',
                2 <= $score => 'light',
                default => 'minimal',
            },
            'score' => $score,
            'estimatedOperations' => $totalIterations,
        ];
    }

    /**
     * @param array{level: string, score: float, estimatedOperations: int} $complexity
     */
    private static function calculateWarmupIterations(array $complexity): int
    {
        return match ($complexity['level']) {
            'extreme' => 3,
            'heavy' => 5,
            'moderate' => 10,
            'light' => 15,
            default => 20,
        };
    }

    /**
     * @param array{level: string, score: float, estimatedOperations: int} $complexity
     */
    private static function calculateInnerIterations(array $complexity): int
    {
        $targetOperations = match ($complexity['level']) {
            'extreme' => 100_000,
            'heavy' => 1_000_000,
            'moderate' => 10_000_000,
            'light' => 50_000_000,
            default => 100_000_000,
        };

        $estimatedOps = $complexity['estimatedOperations'];
        if (0 < $estimatedOps) {
            $suggested = (int) ($targetOperations / $estimatedOps);

            return max(self::MIN_INNER_ITERATIONS, min(1000, $suggested));
        }

        return self::DEFAULT_INNER_ITERATIONS;
    }

    private function validate(): void
    {
        if (self::MIN_WARMUP_ITERATIONS > $this->warmupIterations) {
            throw new InvalidArgumentException(sprintf('Warmup iterations must be at least %d', self::MIN_WARMUP_ITERATIONS));
        }

        if (self::MAX_WARMUP_ITERATIONS < $this->warmupIterations) {
            throw new InvalidArgumentException(sprintf('Warmup iterations must not exceed %d', self::MAX_WARMUP_ITERATIONS));
        }

        if (self::MIN_INNER_ITERATIONS > $this->innerIterations) {
            throw new InvalidArgumentException(sprintf('Inner iterations must be at least %d', self::MIN_INNER_ITERATIONS));
        }

        if (self::MAX_INNER_ITERATIONS < $this->innerIterations) {
            throw new InvalidArgumentException(sprintf('Inner iterations must not exceed %d', self::MAX_INNER_ITERATIONS));
        }
    }
}
