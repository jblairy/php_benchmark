<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Tests\Unit\Infrastructure\Execution\ScriptBuilding;

use Jblairy\PhpBenchmark\Infrastructure\Execution\ScriptBuilding\ScriptBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ScriptBuilder with dependency injection.
 */
final class ScriptBuilderTest extends TestCase
{
    public function testBuildWithCustomIterations(): void
    {
        // Can inject any values for testing
        $scriptBuilder = new ScriptBuilder(
            warmupIterations: 5,
            innerIterations: 100,
        );

        $script = $scriptBuilder->build('$x = 1 + 1;');

        // Verify warmup loop is generated with correct iteration count
        self::assertStringContainsString('for ($warmup = 0; $warmup < 5; ++$warmup)', $script);

        // Verify inner loop is generated with correct iteration count
        self::assertStringContainsString('for ($inner = 0; $inner < 100; ++$inner)', $script);

        // Verify the method body is included in both loops
        self::assertStringContainsString('$x = 1 + 1;', $script);
    }

    public function testBuildWithDefaultIterations(): void
    {
        // Can use defaults (10 warmup, 1000 inner)
        $scriptBuilder = new ScriptBuilder();

        $script = $scriptBuilder->build('$x = 1 + 1;');

        // Verify default warmup iterations
        self::assertStringContainsString('for ($warmup = 0; $warmup < 10; ++$warmup)', $script);

        // Verify default inner iterations
        self::assertStringContainsString('for ($inner = 0; $inner < 1000; ++$inner)', $script);
    }

    public function testBuildIncludesMemoryTracking(): void
    {
        $scriptBuilder = new ScriptBuilder(warmupIterations: 1, innerIterations: 1);

        $script = $scriptBuilder->build('$x = 1;');

        // Verify memory tracking is included
        self::assertStringContainsString('memory_get_usage(true)', $script);
        self::assertStringContainsString('memory_get_peak_usage(true)', $script);

        // Verify memory is tracked before and after
        self::assertStringContainsString('$mem_before = memory_get_usage(true);', $script);
        self::assertStringContainsString('$mem_after = memory_get_usage(true);', $script);
    }

    public function testBuildIncludesHighPrecisionTiming(): void
    {
        $scriptBuilder = new ScriptBuilder(warmupIterations: 1, innerIterations: 1);

        $script = $scriptBuilder->build('$x = 1;');

        // Verify hrtime is used for high precision timing
        self::assertStringContainsString('hrtime(true)', $script);
        self::assertStringContainsString('$start_time = hrtime(true);', $script);
        self::assertStringContainsString('$end_time = hrtime(true);', $script);
    }

    public function testBuildIncludesJsonOutput(): void
    {
        $scriptBuilder = new ScriptBuilder(warmupIterations: 1, innerIterations: 1);

        $script = $scriptBuilder->build('$x = 1;');

        // Verify JSON output is generated
        self::assertStringContainsString('json_encode', $script);

        // Verify all required metrics are included
        self::assertStringContainsString('"execution_time_ms"', $script);
        self::assertStringContainsString('"memory_used_bytes"', $script);
        self::assertStringContainsString('"memory_peak_bytes"', $script);
        self::assertStringContainsString('"inner_iterations"', $script);
        self::assertStringContainsString('"warmup_iterations"', $script);
    }

    public function testBuildWithComplexMethodBody(): void
    {
        $scriptBuilder = new ScriptBuilder(warmupIterations: 2, innerIterations: 50);

        $methodBody = <<<'PHP'
            $array = [];
            for ($i = 0; $i < 100; ++$i) {
                $array[] = $i * 2;
            }
            PHP;

        $script = $scriptBuilder->build($methodBody);

        // Verify complex method body is included
        self::assertStringContainsString('$array = [];', $script);
        self::assertStringContainsString('for ($i = 0; $i < 100; ++$i)', $script);
        self::assertStringContainsString('$array[] = $i * 2;', $script);

        // Verify iteration counts are correct
        self::assertStringContainsString('for ($warmup = 0; $warmup < 2; ++$warmup)', $script);
        self::assertStringContainsString('for ($inner = 0; $inner < 50; ++$inner)', $script);
    }

    public function testBuildCalculatesAverageTime(): void
    {
        $scriptBuilder = new ScriptBuilder(warmupIterations: 1, innerIterations: 1000);

        $script = $scriptBuilder->build('$x = 1;');

        // Verify average time calculation
        self::assertStringContainsString('$avg_time_ms = $total_time_ms / 1000;', $script);

        // Verify nanoseconds to milliseconds conversion
        self::assertStringContainsString('$total_time_ms = $elapsed_ns / 1_000_000;', $script);
    }

    public function testBuildWithZeroWarmupIterations(): void
    {
        $scriptBuilder = new ScriptBuilder(warmupIterations: 0, innerIterations: 100);

        $script = $scriptBuilder->build('$x = 1;');

        // Verify warmup loop is still generated (even with 0 iterations)
        self::assertStringContainsString('for ($warmup = 0; $warmup < 0; ++$warmup)', $script);

        // Verify inner loop is correct
        self::assertStringContainsString('for ($inner = 0; $inner < 100; ++$inner)', $script);
    }

    public function testBuildWithLargeIterationCounts(): void
    {
        $scriptBuilder = new ScriptBuilder(warmupIterations: 1000, innerIterations: 1_000_000);

        $script = $scriptBuilder->build('$x = 1;');

        // Verify large iteration counts are handled correctly
        self::assertStringContainsString('for ($warmup = 0; $warmup < 1000; ++$warmup)', $script);
        self::assertStringContainsString('for ($inner = 0; $inner < 1000000; ++$inner)', $script);
    }

    public function testBuildPreservesMethodBodyEscaping(): void
    {
        $scriptBuilder = new ScriptBuilder(warmupIterations: 1, innerIterations: 1);

        // Method body with special characters that need escaping
        $methodBody = '$x = "test"; $y = \'single\'; $z = $var;';

        $script = $scriptBuilder->build($methodBody);

        // Verify method body is preserved
        self::assertStringContainsString($methodBody, $script);
    }
}
