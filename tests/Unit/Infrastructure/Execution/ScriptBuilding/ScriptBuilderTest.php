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
        $builder = new ScriptBuilder(
            warmupIterations: 5,
            innerIterations: 100,
        );

        $script = $builder->build('$x = 1 + 1;');

        // Verify warmup loop is generated with correct iteration count
        $this->assertStringContainsString('for ($warmup = 0; $warmup < 5; ++$warmup)', $script);

        // Verify inner loop is generated with correct iteration count
        $this->assertStringContainsString('for ($inner = 0; $inner < 100; ++$inner)', $script);

        // Verify the method body is included in both loops
        $this->assertStringContainsString('$x = 1 + 1;', $script);
    }

    public function testBuildWithDefaultIterations(): void
    {
        // Can use defaults (10 warmup, 1000 inner)
        $builder = new ScriptBuilder();

        $script = $builder->build('$x = 1 + 1;');

        // Verify default warmup iterations
        $this->assertStringContainsString('for ($warmup = 0; $warmup < 10; ++$warmup)', $script);

        // Verify default inner iterations
        $this->assertStringContainsString('for ($inner = 0; $inner < 1000; ++$inner)', $script);
    }

    public function testBuildIncludesMemoryTracking(): void
    {
        $builder = new ScriptBuilder(warmupIterations: 1, innerIterations: 1);

        $script = $builder->build('$x = 1;');

        // Verify memory tracking is included
        $this->assertStringContainsString('memory_get_usage(true)', $script);
        $this->assertStringContainsString('memory_get_peak_usage(true)', $script);

        // Verify memory is tracked before and after
        $this->assertStringContainsString('$mem_before = memory_get_usage(true);', $script);
        $this->assertStringContainsString('$mem_after = memory_get_usage(true);', $script);
    }

    public function testBuildIncludesHighPrecisionTiming(): void
    {
        $builder = new ScriptBuilder(warmupIterations: 1, innerIterations: 1);

        $script = $builder->build('$x = 1;');

        // Verify hrtime is used for high precision timing
        $this->assertStringContainsString('hrtime(true)', $script);
        $this->assertStringContainsString('$start_time = hrtime(true);', $script);
        $this->assertStringContainsString('$end_time = hrtime(true);', $script);
    }

    public function testBuildIncludesJsonOutput(): void
    {
        $builder = new ScriptBuilder(warmupIterations: 1, innerIterations: 1);

        $script = $builder->build('$x = 1;');

        // Verify JSON output is generated
        $this->assertStringContainsString('json_encode', $script);

        // Verify all required metrics are included
        $this->assertStringContainsString('"execution_time_ms"', $script);
        $this->assertStringContainsString('"memory_used_bytes"', $script);
        $this->assertStringContainsString('"memory_peak_bytes"', $script);
        $this->assertStringContainsString('"inner_iterations"', $script);
        $this->assertStringContainsString('"warmup_iterations"', $script);
    }

    public function testBuildWithComplexMethodBody(): void
    {
        $builder = new ScriptBuilder(warmupIterations: 2, innerIterations: 50);

        $methodBody = <<<'PHP'
            $array = [];
            for ($i = 0; $i < 100; ++$i) {
                $array[] = $i * 2;
            }
            PHP;

        $script = $builder->build($methodBody);

        // Verify complex method body is included
        $this->assertStringContainsString('$array = [];', $script);
        $this->assertStringContainsString('for ($i = 0; $i < 100; ++$i)', $script);
        $this->assertStringContainsString('$array[] = $i * 2;', $script);

        // Verify iteration counts are correct
        $this->assertStringContainsString('for ($warmup = 0; $warmup < 2; ++$warmup)', $script);
        $this->assertStringContainsString('for ($inner = 0; $inner < 50; ++$inner)', $script);
    }

    public function testBuildCalculatesAverageTime(): void
    {
        $builder = new ScriptBuilder(warmupIterations: 1, innerIterations: 1000);

        $script = $builder->build('$x = 1;');

        // Verify average time calculation
        $this->assertStringContainsString('$avg_time_ms = $total_time_ms / 1000;', $script);

        // Verify nanoseconds to milliseconds conversion
        $this->assertStringContainsString('$total_time_ms = $elapsed_ns / 1_000_000;', $script);
    }

    public function testBuildWithZeroWarmupIterations(): void
    {
        $builder = new ScriptBuilder(warmupIterations: 0, innerIterations: 100);

        $script = $builder->build('$x = 1;');

        // Verify warmup loop is still generated (even with 0 iterations)
        $this->assertStringContainsString('for ($warmup = 0; $warmup < 0; ++$warmup)', $script);

        // Verify inner loop is correct
        $this->assertStringContainsString('for ($inner = 0; $inner < 100; ++$inner)', $script);
    }

    public function testBuildWithLargeIterationCounts(): void
    {
        $builder = new ScriptBuilder(warmupIterations: 1000, innerIterations: 1_000_000);

        $script = $builder->build('$x = 1;');

        // Verify large iteration counts are handled correctly
        $this->assertStringContainsString('for ($warmup = 0; $warmup < 1000; ++$warmup)', $script);
        $this->assertStringContainsString('for ($inner = 0; $inner < 1000000; ++$inner)', $script);
    }

    public function testBuildPreservesMethodBodyEscaping(): void
    {
        $builder = new ScriptBuilder(warmupIterations: 1, innerIterations: 1);

        // Method body with special characters that need escaping
        $methodBody = '$x = "test"; $y = \'single\'; $z = $var;';

        $script = $builder->build($methodBody);

        // Verify method body is preserved
        $this->assertStringContainsString($methodBody, $script);
    }
}
