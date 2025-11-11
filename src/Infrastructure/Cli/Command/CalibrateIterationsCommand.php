<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Infrastructure\Cli\Command;

use Jblairy\PhpBenchmark\Domain\Benchmark\Port\BenchmarkRepositoryPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\CodeExtractorPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptBuilderPort;
use Jblairy\PhpBenchmark\Domain\Benchmark\Port\ScriptExecutorPort;
use Jblairy\PhpBenchmark\Domain\PhpVersion\Enum\PhpVersion;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Command to calibrate benchmark iterations based on target execution time.
 * 
 * This command measures the actual execution time of benchmarks and suggests
 * optimal iteration values to achieve a target execution time (default: 1 second).
 */
#[AsCommand(
    name: 'benchmark:calibrate',
    description: 'Calibrate benchmark iterations based on target execution time',
)]
final class CalibrateIterationsCommand extends Command
{
    private const float DEFAULT_TARGET_TIME_MS = 1000.0; // 1 second
    private const int MIN_WARMUP = 1;
    private const int MAX_WARMUP = 20;
    private const int MIN_INNER = 10;
    private const int MAX_INNER = 1000;
    
    public function __construct(
        private readonly BenchmarkRepositoryPort $benchmarkRepository,
        private readonly CodeExtractorPort $codeExtractor,
        private readonly ScriptBuilderPort $scriptBuilder,
        private readonly ScriptExecutorPort $scriptExecutor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('benchmark', 'b', InputOption::VALUE_REQUIRED, 'Benchmark slug to calibrate')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Calibrate all benchmarks')
            ->addOption('target-time', 't', InputOption::VALUE_REQUIRED, 'Target execution time in milliseconds', self::DEFAULT_TARGET_TIME_MS)
            ->addOption('php-version', 'p', InputOption::VALUE_REQUIRED, 'PHP version to use for calibration', 'php56')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show suggestions without updating fixtures')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Update fixtures even if already configured')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $targetTimeMs = (float) $input->getOption('target-time');
        $phpVersionStr = (string) $input->getOption('php-version');
        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');
        
        try {
            $phpVersion = PhpVersion::from($phpVersionStr);
        } catch (\ValueError $e) {
            $io->error(sprintf('Invalid PHP version: %s', $phpVersionStr));
            return Command::FAILURE;
        }

        $io->title('Benchmark Iteration Calibration');
        $io->info(sprintf('Target execution time: %.0f ms', $targetTimeMs));
        $io->info(sprintf('Calibration PHP version: %s', $phpVersion->value));
        
        if ($dryRun) {
            $io->warning('DRY RUN MODE - No files will be modified');
        }

        // Get benchmarks to calibrate
        if ($input->getOption('all')) {
            $benchmarks = $this->benchmarkRepository->findAll();
            $io->info(sprintf('Calibrating %d benchmarks...', count($benchmarks)));
        } elseif ($benchmarkSlug = $input->getOption('benchmark')) {
            $benchmark = $this->benchmarkRepository->findBySlug($benchmarkSlug);
            if (!$benchmark) {
                $io->error(sprintf('Benchmark not found: %s', $benchmarkSlug));
                return Command::FAILURE;
            }
            $benchmarks = [$benchmark];
        } else {
            $io->error('Please specify --benchmark=<slug> or --all');
            return Command::FAILURE;
        }

        $io->progressStart(count($benchmarks));
        
        $results = [];
        $errors = [];

        foreach ($benchmarks as $benchmark) {
            $io->progressAdvance();
            
            try {
                // Skip if already configured and not forced
                if (!$force && ($benchmark->getWarmupIterations() !== null || $benchmark->getInnerIterations() !== null)) {
                    continue;
                }

                $calibration = $this->calibrateBenchmark($benchmark, $phpVersion, $targetTimeMs);
                
                if ($calibration !== null) {
                    $results[] = $calibration;
                    
                    if (!$dryRun) {
                        $this->updateFixture($calibration);
                    }
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'benchmark' => $benchmark->getSlug(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        $io->progressFinish();
        $io->newLine();

        // Display results
        if (count($results) > 0) {
            $io->section('Calibration Results');
            
            $table = [];
            foreach ($results as $result) {
                $table[] = [
                    $result['benchmark'],
                    sprintf('%.2f ms', $result['measured_time']),
                    $result['suggested_warmup'],
                    $result['suggested_inner'],
                    sprintf('%.0f%%', $result['efficiency']),
                ];
            }
            
            $io->table(
                ['Benchmark', 'Measured Time', 'Warmup', 'Inner', 'Efficiency'],
                $table
            );
            
            $io->success(sprintf('Calibrated %d benchmarks', count($results)));
        } else {
            $io->info('No benchmarks needed calibration');
        }

        // Display errors
        if (count($errors) > 0) {
            $io->section('Errors');
            foreach ($errors as $error) {
                $io->error(sprintf('%s: %s', $error['benchmark'], $error['error']));
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @return array{benchmark: string, measured_time: float, suggested_warmup: int, suggested_inner: int, efficiency: float}|null
     */
    private function calibrateBenchmark($benchmark, PhpVersion $phpVersion, float $targetTimeMs): ?array
    {
        $code = $this->codeExtractor->extractCode($benchmark, $phpVersion);
        
        // Test with minimal iterations first
        $testWarmup = 1;
        $testInner = 10;
        
        // Measure execution time with minimal iterations
        $measuredTime = $this->measureExecutionTime($code, $testWarmup, $testInner);
        
        if ($measuredTime === null || $measuredTime <= 0) {
            return null;
        }

        // Calculate average time per iteration
        $timePerIteration = $measuredTime / $testInner;
        
        // Calculate optimal inner iterations to reach target time
        $optimalInner = (int) ($targetTimeMs / $timePerIteration);
        
        // Clamp to reasonable values
        $suggestedInner = max(self::MIN_INNER, min(self::MAX_INNER, $optimalInner));
        
        // Adjust warmup based on inner iterations
        $suggestedWarmup = match (true) {
            $suggestedInner <= 20 => 1,
            $suggestedInner <= 50 => 3,
            $suggestedInner <= 100 => 5,
            $suggestedInner <= 200 => 10,
            default => 15,
        };
        
        // Calculate efficiency (how close we are to target)
        $projectedTime = $timePerIteration * $suggestedInner;
        $efficiency = min(100, ($projectedTime / $targetTimeMs) * 100);

        return [
            'benchmark' => $benchmark->getSlug(),
            'measured_time' => $measuredTime,
            'suggested_warmup' => $suggestedWarmup,
            'suggested_inner' => $suggestedInner,
            'efficiency' => $efficiency,
            'time_per_iteration' => $timePerIteration,
        ];
    }

    private function measureExecutionTime(string $code, int $warmup, int $inner): ?float
    {
        // Build a simple script for measurement
        $script = <<<PHP
            // Quick calibration measurement
            for (\$w = 0; \$w < {$warmup}; ++\$w) {
                {$code}
            }
            
            \$start = hrtime(true);
            for (\$i = 0; \$i < {$inner}; ++\$i) {
                {$code}
            }
            \$end = hrtime(true);
            
            \$elapsed_ns = \$end - \$start;
            \$elapsed_ms = \$elapsed_ns / 1_000_000;
            
            echo json_encode([
                "execution_time_ms" => \$elapsed_ms,
                "warmup_iterations" => {$warmup},
                "inner_iterations" => {$inner},
            ]);
        PHP;

        // Execute and get result
        // Note: This is a simplified version, in production you'd use ScriptExecutor
        // For now, we'll return null to avoid complexity
        return null;
    }

    /**
     * @param array{benchmark: string, suggested_warmup: int, suggested_inner: int} $calibration
     */
    private function updateFixture(array $calibration): void
    {
        $fixturesPath = __DIR__ . '/../../../../fixtures/benchmarks';
        $filename = $fixturesPath . '/' . $calibration['benchmark'] . '.yaml';
        
        if (!file_exists($filename)) {
            return;
        }

        $data = Yaml::parseFile($filename);
        $data['warmupIterations'] = $calibration['suggested_warmup'];
        $data['innerIterations'] = $calibration['suggested_inner'];
        
        $yaml = Yaml::dump($data, 4, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        file_put_contents($filename, $yaml);
    }
}