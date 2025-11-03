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

                $benchmark = $this->createBenchmarkFromYaml($data, $file->getFilename());
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
        // Validate required fields exist
        $requiredFields = ['slug', 'name', 'category', 'code', 'phpVersions'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException(sprintf('Missing required field "%s" in %s', $field, $filename));
            }
        }

        // Extract and validate string fields
        $slug = is_string($data['slug']) ? $data['slug'] : throw new InvalidArgumentException(sprintf('slug must be string in %s', $filename));
        $name = is_string($data['name']) ? $data['name'] : throw new InvalidArgumentException(sprintf('name must be string in %s', $filename));
        $category = is_string($data['category']) ? $data['category'] : throw new InvalidArgumentException(sprintf('category must be string in %s', $filename));
        $code = is_string($data['code']) ? mb_trim($data['code']) : throw new InvalidArgumentException(sprintf('code must be string in %s', $filename));
        $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : '';
        
        // Validate code is not empty
        if (empty($code)) {
            throw new InvalidArgumentException(sprintf('Code field cannot be empty in %s', $filename));
        }

        // Validate phpVersions is an array
        if (!is_array($data['phpVersions']) || empty($data['phpVersions'])) {
            throw new InvalidArgumentException(sprintf('phpVersions must be a non-empty array in %s', $filename));
        }
        
        // Extract and validate array fields
        $tags = isset($data['tags']) && is_array($data['tags']) ? $data['tags'] : [];
        $icon = isset($data['icon']) && is_string($data['icon']) ? $data['icon'] : null;

        return new Benchmark(
            slug: $slug,
            name: $name,
            category: $category,
            description: $description,
            code: $code,
            phpVersions: $data['phpVersions'],
            tags: $tags,
            icon: $icon,
        );
    }
}
