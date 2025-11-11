<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for validating benchmark fixture data from YAML files.
 */
final readonly class BenchmarkFixtureData
{
    /**
     * @param string[] $phpVersions
     * @param string[] $tags
     */
    public function __construct(
        #[Assert\NotBlank(message: 'slug field is required')]
        public string $slug,
        #[Assert\NotBlank(message: 'name field is required')]
        public string $name,
        #[Assert\NotBlank(message: 'category field is required')]
        public string $category,
        #[Assert\NotBlank(message: 'code field is required')]
        public string $code,
        #[Assert\NotBlank]
        #[Assert\Count(min: 1, minMessage: 'phpVersions must contain at least one version')]
        #[Assert\All([
            new Assert\Type('string'),
        ])]
        public array $phpVersions,
        public string $description = '',
        #[Assert\All([
            new Assert\Type('string'),
        ])]
        public array $tags = [],
        public ?string $icon = null,
        #[Assert\Positive(message: 'warmupIterations must be positive')]
        #[Assert\LessThanOrEqual(100, message: 'warmupIterations must not exceed 100')]
        public ?int $warmupIterations = null,
        #[Assert\Positive(message: 'innerIterations must be positive')]
        #[Assert\LessThanOrEqual(10000, message: 'innerIterations must not exceed 10000')]
        public ?int $innerIterations = null,
    ) {
    }
}
