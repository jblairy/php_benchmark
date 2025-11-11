<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Tests\Unit\Domain\Dashboard\Service;

use Jblairy\PhpBenchmark\Domain\Dashboard\Service\OutlierDetector;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for OutlierDetector.
 */
final class OutlierDetectorTest extends TestCase
{
    private OutlierDetector $outlierDetector;

    protected function setUp(): void
    {
        $this->outlierDetector = new OutlierDetector();
    }

    public function testDetectAndRemoveWithNoOutliers(): void
    {
        // Arrange - Normal distribution without outliers
        $data = [10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7, 10.8, 10.9, 11.0];

        // Act
        $result = $this->outlierDetector->detectAndRemove($data);

        // Assert
        self::assertCount(10, $result->cleanedData);
        self::assertCount(0, $result->outliers);
        self::assertFalse($result->hasOutliers());
        self::assertEqualsWithDelta(0.0, $result->getOutlierPercentage(), PHP_FLOAT_EPSILON);
    }

    public function testDetectAndRemoveWithClearOutliers(): void
    {
        // Arrange - Data with clear outliers
        $data = [
            10.0, 10.1, 10.2, 10.3, 10.4, // Normal values
            50.0, // Clear outlier
            10.5, 10.6, 10.7, 10.8, // More normal values
            0.5,  // Another outlier
        ];

        // Act
        $result = $this->outlierDetector->detectAndRemove($data);

        // Assert
        self::assertCount(9, $result->cleanedData);
        self::assertCount(2, $result->outliers);
        self::assertTrue($result->hasOutliers());
        self::assertContains(50.0, $result->outliers);
        self::assertContains(0.5, $result->outliers);
        self::assertEqualsWithDelta(18.18, $result->getOutlierPercentage(), 0.01);
    }

    public function testDetectAndRemoveWithInsufficientData(): void
    {
        // Arrange - Too few data points
        $data = [10.0, 20.0, 30.0];

        // Act
        $result = $this->outlierDetector->detectAndRemove($data);

        // Assert - Should return original data
        self::assertCount(3, $result->cleanedData);
        self::assertCount(0, $result->outliers);
        self::assertFalse($result->hasOutliers());
    }

    public function testDetectAndRemoveWithEmptyData(): void
    {
        // Arrange
        $data = [];

        // Act
        $result = $this->outlierDetector->detectAndRemove($data);

        // Assert
        self::assertCount(0, $result->cleanedData);
        self::assertCount(0, $result->outliers);
        self::assertFalse($result->hasOutliers());
    }

    public function testCalculateQuartilesCorrectly(): void
    {
        // Arrange - Known dataset where Q1=25, Q3=75
        $data = range(1, 100); // 1 to 100

        // Act
        $result = $this->outlierDetector->detectAndRemove($data);

        // Assert - With IQR=50, bounds should be [-50, 150]
        // So all values 1-100 should be included
        self::assertCount(100, $result->cleanedData);
        self::assertCount(0, $result->outliers);
    }

    public function testDetectWithModifiedZScore(): void
    {
        // Arrange - Data with outliers
        $data = [
            10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, // Normal range
            100, // Extreme outlier
            -50, // Another extreme outlier
        ];

        // Act
        $result = $this->outlierDetector->detectWithModifiedZScore($data, 3.5);

        // Assert
        self::assertCount(11, $result->cleanedData);
        self::assertCount(2, $result->outliers);
        self::assertContains(100.0, $result->outliers);
        self::assertContains(-50.0, $result->outliers);
    }

    public function testRealBenchmarkData(): void
    {
        // Arrange - Simulated benchmark data with occasional spikes
        $data = [
            12.5, 12.7, 12.6, 12.8, 12.9, // Normal execution times
            13.0, 12.4, 12.6, 12.7, 12.5,
            45.2, // GC pause or system interrupt
            12.6, 12.8, 12.7, 12.9, 13.1,
            12.4, 12.5, 12.7, 12.6,
            98.7, // Another spike
        ];

        // Act
        $result = $this->outlierDetector->detectAndRemove($data);

        // Assert
        self::assertCount(19, $result->cleanedData); // Should remove 2 outliers
        self::assertCount(2, $result->outliers);
        self::assertContains(45.2, $result->outliers);
        self::assertContains(98.7, $result->outliers);

        // Verify bounds make sense
        self::assertGreaterThan(10.0, $result->lowerBound);
        self::assertLessThan(15.0, $result->upperBound);
    }

    public function testOutlierSummary(): void
    {
        // Arrange
        $data = [10, 11, 12, 13, 14, 15, 100, 200]; // 2 outliers

        // Act
        $result = $this->outlierDetector->detectAndRemove($data);

        // Assert
        $summary = $result->getSummary();
        self::assertStringContainsString('2 outliers', $summary);
        self::assertStringContainsString('25.0%', $summary);
        self::assertStringContainsString('8 samples', $summary);
    }

    public function testLinearInterpolationInQuartileCalculation(): void
    {
        // Arrange - Small dataset to test interpolation
        $data = [1.0, 2.0, 3.0, 4.0, 5.0];

        // Act
        $result = $this->outlierDetector->detectAndRemove($data);

        // Assert - All values should be within bounds
        self::assertCount(5, $result->cleanedData);
        self::assertCount(0, $result->outliers);

        // For this dataset: Q1=1.5, Q3=4.5, IQR=3
        // Bounds: [-3, 9]
        self::assertEqualsWithDelta(-2.5, $result->lowerBound, 0.1);
        self::assertEqualsWithDelta(8.5, $result->upperBound, 0.1);
    }
}
