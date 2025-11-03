<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use InvalidArgumentException;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Benchmark;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads benchmarks from YAML fixture files
 * Each YAML file represents one benchmark.
 */
class YamlBenchmarkFixtures extends Fixture
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $fixturesPath = $this->projectDir . '/fixtures/benchmarks';

        if (!is_dir($fixturesPath)) {
            throw new RuntimeException("Fixtures directory not found: {$fixturesPath}");
        }

        $finder = new Finder();
        $finder->files()
            ->in($fixturesPath)
            ->name('*.yaml')
            ->name('*.yml')
            ->sortByName();

        foreach ($finder as $file) {
            try {
                $realPath = $file->getRealPath();
                if (false === $realPath) {
                    continue;
                }

                $data = Yaml::parseFile($realPath);

                if (!is_array($data)) {
                    continue;
                }

                // Ensure it's an associative array with string keys
                /** @var array<string, mixed> $validData */
                $validData = array_filter(
                    $data,
                    fn ($key): bool => is_string($key),
                    ARRAY_FILTER_USE_KEY,
                );

                $benchmark = $this->createBenchmarkFromYaml($validData, $file->getFilename());
                $manager->persist($benchmark);
            } catch (Exception $e) {
                // Log error but continue loading other fixtures
                error_log(sprintf(
                    'Failed to load benchmark fixture %s: %s',
                    $file->getFilename(),
                    $e->getMessage(),
                ));
            }
        }

        $manager->flush();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createBenchmarkFromYaml(array $data, string $filename): Benchmark
    {
        // Validate and extract slug
        if (!isset($data['slug']) || !is_string($data['slug'])) {
            throw new InvalidArgumentException(sprintf('slug field is required and must be string in %s', $filename));
        }
        $slug = $data['slug'];

        // Validate and extract name
        if (!isset($data['name']) || !is_string($data['name'])) {
            throw new InvalidArgumentException(sprintf('name field is required and must be string in %s', $filename));
        }
        $name = $data['name'];

        // Validate and extract category
        if (!isset($data['category']) || !is_string($data['category'])) {
            throw new InvalidArgumentException(sprintf('category field is required and must be string in %s', $filename));
        }
        $category = $data['category'];

        // Validate and extract code
        if (!isset($data['code']) || !is_string($data['code'])) {
            throw new InvalidArgumentException(sprintf('code field is required and must be string in %s', $filename));
        }
        $code = mb_trim($data['code']);

        // Validate code is not empty (strict comparison instead of empty())
        if ('' === $code) {
            throw new InvalidArgumentException(sprintf('Code field cannot be empty in %s', $filename));
        }

        // Validate phpVersions
        if (!isset($data['phpVersions']) || !is_array($data['phpVersions']) || 0 === count($data['phpVersions'])) {
            throw new InvalidArgumentException(sprintf('phpVersions must be a non-empty array in %s', $filename));
        }

        // Optional description
        $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : '';

        // Extract and validate array fields
        $rawTags = isset($data['tags']) && is_array($data['tags']) ? $data['tags'] : [];
        $tags = array_values(array_filter($rawTags, 'is_string'));

        // Validate phpVersions array contains only strings
        $phpVersions = array_values(array_filter($data['phpVersions'], 'is_string'));

        $icon = isset($data['icon']) && is_string($data['icon']) ? $data['icon'] : null;

        return new Benchmark(
            slug: $slug,
            name: $name,
            category: $category,
            description: $description,
            code: $code,
            phpVersions: $phpVersions,
            tags: $tags,
            icon: $icon,
        );
    }
}
