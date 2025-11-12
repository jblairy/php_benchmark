<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Symfony\Component\Yaml\Yaml;

/**
 * Manages YAML file operations for benchmark fixtures.
 */
final readonly class YamlFileManager
{
    /**
     * @return array<mixed>|null
     */
    public function readBenchmarkData(string $file): ?array
    {
        $data = Yaml::parseFile($file);

        return is_array($data) ? $data : null;
    }

    public function updateBenchmarkIterations(string $filename, int $warmup, int $inner): void
    {
        $data = $this->readBenchmarkData($filename);
        if (null === $data) {
            return;
        }

        $data['warmupIterations'] = $warmup;
        $data['innerIterations'] = $inner;

        $yaml = Yaml::dump($data, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        file_put_contents($filename, $yaml);
    }
}
