<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Fixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Jblairy\PhpBenchmark\Infrastructure\Persistence\Doctrine\Entity\Benchmark;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Loads benchmarks from YAML fixture files
 * Each YAML file represents one benchmark.
 */
class YamlBenchmarkFixtures extends Fixture
{
    public function __construct(
        private readonly string $projectDir,
        private readonly ValidatorInterface $validator,
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
                $this->logFixtureLoadingError($file->getFilename(), $e);
            }
        }

        $manager->flush();
    }

    /**
     * @param array<mixed> $data
     */
    private function createBenchmarkFromYaml(array $data, string $filename): Benchmark
    {
        $fixtureData = new BenchmarkFixtureData(
            slug: $this->extractStringField($data, 'slug'),
            name: $this->extractStringField($data, 'name'),
            category: $this->extractStringField($data, 'category'),
            code: $this->extractCodeField($data),
            phpVersions: $this->extractStringArray($data, 'phpVersions'),
            description: $this->extractStringField($data, 'description'),
            tags: $this->extractStringArray($data, 'tags'),
            icon: $this->extractNullableStringField($data, 'icon'),
            warmupIterations: $this->extractNullableIntField($data, 'warmupIterations'),
            innerIterations: $this->extractNullableIntField($data, 'innerIterations'),
        );

        $this->validateFixtureData($fixtureData, $filename);

        return $this->buildBenchmarkEntity($fixtureData);
    }

    /**
     * @param array<mixed> $data
     */
    private function extractStringField(array $data, string $key): string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : '';
    }

    /**
     * @param array<mixed> $data
     */
    private function extractNullableStringField(array $data, string $key): ?string
    {
        return isset($data[$key]) && is_string($data[$key]) ? $data[$key] : null;
    }

    /**
     * @param array<mixed> $data
     */
    private function extractCodeField(array $data): string
    {
        $code = $this->extractStringField($data, 'code');

        return '' !== $code ? mb_trim($code) : '';
    }

    /**
     * @param array<mixed> $data
     */
    private function extractNullableIntField(array $data, string $key): ?int
    {
        return isset($data[$key]) && is_numeric($data[$key]) ? (int) $data[$key] : null;
    }

    private function validateFixtureData(BenchmarkFixtureData $fixtureData, string $filename): void
    {
        $violations = $this->validator->validate($fixtureData);

        if (0 < count($violations)) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
            }

            throw new RuntimeException(sprintf('Validation failed for %s: %s', $filename, implode(', ', $errors)));
        }
    }

    private function buildBenchmarkEntity(BenchmarkFixtureData $fixtureData): Benchmark
    {
        return new Benchmark(
            slug: $fixtureData->slug,
            name: $fixtureData->name,
            category: $fixtureData->category,
            description: $fixtureData->description,
            code: $fixtureData->code,
            phpVersions: $fixtureData->phpVersions,
            tags: $fixtureData->tags,
            icon: $fixtureData->icon,
            warmupIterations: $fixtureData->warmupIterations ?? null,
            innerIterations: $fixtureData->innerIterations ?? null,
        );
    }

    /**
     * @param array<mixed> $data
     *
     * @return string[]
     */
    private function extractStringArray(array $data, string $key): array
    {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            return [];
        }

        return array_values(array_filter($data[$key], 'is_string'));
    }

    private function logFixtureLoadingError(string $filename, Exception $exception): void
    {
        error_log(sprintf(
            'Failed to load benchmark fixture %s: %s',
            $filename,
            $exception->getMessage(),
        ));
    }
}
