<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Service;

use Redis;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function extension_loaded;
use function is_int;
use function is_numeric;
use function is_string;
use function mb_trim;
use function parse_url;
use function sprintf;
use function str_starts_with;

/**
 * Shared Redis connection service for CLI commands.
 * Handles connection logic with proper error handling.
 */
final readonly class RedisConnectionService
{
    public function __construct(
        #[Autowire('%env(MESSENGER_TRANSPORT_DSN)%')]
        private string $messengerTransportDsn,
    ) {
    }

    /**
     * @return array{host: string, port: int, database: int}
     */
    public function parseDsn(): array
    {
        if (!str_starts_with($this->messengerTransportDsn, 'redis://')) {
            throw new RuntimeException('MESSENGER_TRANSPORT_DSN must start with redis://');
        }

        $parsedDsn = parse_url($this->messengerTransportDsn);
        if (false === $parsedDsn) {
            throw new RuntimeException('Invalid DSN format');
        }

        $host = is_string($parsedDsn['host'] ?? null) ? $parsedDsn['host'] : 'localhost';
        $port = is_int($parsedDsn['port'] ?? null) ? $parsedDsn['port'] : 6379;
        $pathRaw = $parsedDsn['path'] ?? '';
        $path = is_string($pathRaw) ? mb_trim($pathRaw, '/') : '';
        $database = ('' !== $path && is_numeric($path)) ? (int) $path : 0;

        return [
            'host' => $host,
            'port' => $port,
            'database' => $database,
        ];
    }

    public function connect(float $timeout = 2.0): Redis
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('Redis PHP extension is not installed. Install it with: pecl install redis');
        }

        $config = $this->parseDsn();

        $redis = new Redis();
        $connected = $redis->connect($config['host'], $config['port'], $timeout);

        if (!$connected) {
            throw new RuntimeException(sprintf('Could not connect to Redis at %s:%d', $config['host'], $config['port']));
        }

        if (0 !== $config['database']) {
            $redis->select($config['database']);
        }

        return $redis;
    }

    public function getDsn(): string
    {
        return $this->messengerTransportDsn;
    }
}
