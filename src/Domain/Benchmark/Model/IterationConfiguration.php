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

    // Minimum values for statistical validity
    private const int MIN_WARMUP_ITERATIONS = 1;

    private const int MIN_INNER_ITERATIONS = 10;

    // Maximum reasonable values to prevent excessive runtime
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
     */
    public static function createWithDefaults(
        ?int $warmupIterations = null,
        ?int $innerIterations = null,
        ?string $benchmarkCode = null,
    ): self {
        if (self::hasExplicitValues($warmupIterations, $innerIterations)) {
            return new self(
                $warmupIterations ?? self::DEFAULT_WARMUP_ITERATIONS,
                $innerIterations ?? self::DEFAULT_INNER_ITERATIONS,
            );
        }

        if (self::canCalculateFromCode($benchmarkCode)) {
            return self::createFromCodeComplexity($benchmarkCode, $warmupIterations, $innerIterations);
        }

        return self::createFromEnvironmentDefaults($warmupIterations, $innerIterations);
    }

    /**
     * Get total measurement iterations (excluding warmup).
     */
    public function getTotalMeasurementIterations(): int
    {
        return $this->innerIterations;
    }

    /**
     * Get a description of the configuration.
     */
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
    ): self {
        if (null === $benchmarkCode) {
            return self::createFromEnvironmentDefaults($warmupIterations, $innerIterations);
        }

        $complexity = self::analyzeBenchmarkComplexity($benchmarkCode);

        return new self(
            $warmupIterations ?? self::calculateWarmupIterations($complexity),
            $innerIterations ?? self::calculateInnerIterations($complexity),
        );
    }

    private static function createFromEnvironmentDefaults(
        ?int $warmupIterations,
        ?int $innerIterations,
    ): self {
        return new self(
            $warmupIterations ?? self::getWarmupFromEnvironment(),
            $innerIterations ?? self::getInnerFromEnvironment(),
        );
    }

    private static function getWarmupFromEnvironment(): int
    {
        $envValue = $_ENV['BENCHMARK_WARMUP_ITERATIONS'] ?? self::DEFAULT_WARMUP_ITERATIONS;

        return is_numeric($envValue) ? (int) $envValue : self::DEFAULT_WARMUP_ITERATIONS;
    }

    private static function getInnerFromEnvironment(): int
    {
        $envValue = $_ENV['BENCHMARK_INNER_ITERATIONS'] ?? self::DEFAULT_INNER_ITERATIONS;

        return is_numeric($envValue) ? (int) $envValue : self::DEFAULT_INNER_ITERATIONS;
    }

    /**
     * Analyze benchmark code complexity.
     *
     * @return array{level: string, score: float, estimatedOperations: int}
     */
    private static function analyzeBenchmarkComplexity(string $code): array
    {
        // Count loop iterations
        $loopMatches = [];
        preg_match_all('/for\s*\([^;]+;\s*(\d+)\s*>/', $code, $loopMatches);

        $totalIterations = 1;
        $matches = $loopMatches[1];
        foreach ($matches as $match) {
            $totalIterations *= (int) $match;
        }

        // Check for heavy operations
        $heavyOperations = [
            'mb_' => 2.0,           // Multibyte functions
            'preg_' => 3.0,         // Regex operations
            'hash(' => 3.0,         // Hashing
            'crypt(' => 5.0,        // Cryptography
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

        // Calculate complexity score
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
     * Calculate optimal warmup iterations based on complexity.
     *
     * @param array{level: string, score: float, estimatedOperations: int} $complexity
     */
    private static function calculateWarmupIterations(array $complexity): int
    {
        return match ($complexity['level']) {
            'extreme' => 3,    // Minimal warmup for very heavy benchmarks
            'heavy' => 5,
            'moderate' => 10,
            'light' => 15,
            default => 20,
        };
    }

    /**
     * Calculate optimal inner iterations based on complexity.
     *
     * @param array{level: string, score: float, estimatedOperations: int} $complexity
     */
    private static function calculateInnerIterations(array $complexity): int
    {
        // Target total operations for different complexity levels
        $targetOperations = match ($complexity['level']) {
            'extreme' => 100_000,       // 100K operations max
            'heavy' => 1_000_000,       // 1M operations max
            'moderate' => 10_000_000,   // 10M operations max
            'light' => 50_000_000,      // 50M operations max
            default => 100_000_000,     // 100M operations max
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
