<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Benchmark;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads benchmarks from YAML fixture files
 * Each YAML file represents one benchmark
 */
class YamlBenchmarkFixtures extends Fixture
{
    public function __construct(
        private readonly string $projectDir
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $fixturesPath = $this->projectDir . '/fixtures/benchmarks';

        if (!is_dir($fixturesPath)) {
            throw new \RuntimeException("Fixtures directory not found: {$fixturesPath}");
        }

        $finder = new Finder();
        $finder->files()
            ->in($fixturesPath)
            ->name('*.yaml')
            ->name('*.yml')
            ->sortByName();

        foreach ($finder as $file) {
            try {
                $data = Yaml::parseFile($file->getRealPath());

                if (!is_array($data)) {
                    continue;
                }

                $benchmark = $this->createBenchmarkFromYaml($data, $file->getFilename());
                $manager->persist($benchmark);
            } catch (\Exception $e) {
                // Log error but continue loading other fixtures
                error_log(sprintf(
                    'Failed to load benchmark fixture %s: %s',
                    $file->getFilename(),
                    $e->getMessage()
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
        // Validate required fields
        $requiredFields = ['slug', 'name', 'category', 'code', 'phpVersions'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException(
                    sprintf('Missing required field "%s" in %s', $field, $filename)
                );
            }
        }

        // Validate code is not empty
        if (empty(trim($data['code']))) {
            throw new \InvalidArgumentException(
                sprintf('Code field cannot be empty in %s', $filename)
            );
        }

        // Validate phpVersions is an array
        if (!is_array($data['phpVersions']) || empty($data['phpVersions'])) {
            throw new \InvalidArgumentException(
                sprintf('phpVersions must be a non-empty array in %s', $filename)
            );
        }

        return new Benchmark(
            slug: $data['slug'],
            name: $data['name'],
            category: $data['category'],
            description: $data['description'] ?? '',
            code: trim($data['code']),
            phpVersions: $data['phpVersions'],
            tags: $data['tags'] ?? [],
            icon: $data['icon'] ?? null
        );
    }
}
