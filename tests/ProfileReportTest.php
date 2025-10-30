<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Debug\ProfileReport;

class ProfileReportTest extends TestCase
{
    /**
     * @return array<string, array{count: int, totalTime: float, totalMemory: int, avgTime: float, avgMemory: float}>
     */
    private function createTestMetrics(): array
    {
        return [
            'operation1' => [
                'count' => 5,
                'totalTime' => 1.5,
                'totalMemory' => 1024000,
                'avgTime' => 0.3,
                'avgMemory' => 204800.0,
            ],
            'operation2' => [
                'count' => 3,
                'totalTime' => 0.5,
                'totalMemory' => 512000,
                'avgTime' => 0.166,
                'avgMemory' => 170666.0,
            ],
            'operation3' => [
                'count' => 10,
                'totalTime' => 2.0,
                'totalMemory' => 2048000,
                'avgTime' => 0.2,
                'avgMemory' => 204800.0,
            ],
        ];
    }

    public function testGetMetrics(): void
    {
        $metrics = $this->createTestMetrics();
        $report = new ProfileReport($metrics);

        $this->assertEquals($metrics, $report->getMetrics());
    }

    public function testGetSortedByTime(): void
    {
        $metrics = $this->createTestMetrics();
        $report = new ProfileReport($metrics);

        $sorted = $report->getSortedByTime();
        $keys = array_keys($sorted);

        $this->assertEquals('operation3', $keys[0]); // 2.0s
        $this->assertEquals('operation1', $keys[1]); // 1.5s
        $this->assertEquals('operation2', $keys[2]); // 0.5s
    }

    public function testGetSortedByMemory(): void
    {
        $metrics = $this->createTestMetrics();
        $report = new ProfileReport($metrics);

        $sorted = $report->getSortedByMemory();
        $keys = array_keys($sorted);

        $this->assertEquals('operation3', $keys[0]); // 2048000
        $this->assertEquals('operation1', $keys[1]); // 1024000
        $this->assertEquals('operation2', $keys[2]); // 512000
    }

    public function testGetSortedByCount(): void
    {
        $metrics = $this->createTestMetrics();
        $report = new ProfileReport($metrics);

        $sorted = $report->getSortedByCount();
        $keys = array_keys($sorted);

        $this->assertEquals('operation3', $keys[0]); // 10
        $this->assertEquals('operation1', $keys[1]); // 5
        $this->assertEquals('operation2', $keys[2]); // 3
    }

    public function testGetTopByTime(): void
    {
        $metrics = $this->createTestMetrics();
        $report = new ProfileReport($metrics);

        $top = $report->getTopByTime(2);
        $this->assertCount(2, $top);

        $keys = array_keys($top);
        $this->assertEquals('operation3', $keys[0]);
        $this->assertEquals('operation1', $keys[1]);
    }

    public function testGetTopByMemory(): void
    {
        $metrics = $this->createTestMetrics();
        $report = new ProfileReport($metrics);

        $top = $report->getTopByMemory(2);
        $this->assertCount(2, $top);

        $keys = array_keys($top);
        $this->assertEquals('operation3', $keys[0]);
        $this->assertEquals('operation1', $keys[1]);
    }

    public function testToText(): void
    {
        $metrics = $this->createTestMetrics();
        $report = new ProfileReport($metrics);

        $text = $report->toText();
        $this->assertStringContainsString('PROFILING REPORT', $text);
        $this->assertStringContainsString('Summary:', $text);
        $this->assertStringContainsString('operation1', $text);
        $this->assertStringContainsString('operation2', $text);
        $this->assertStringContainsString('operation3', $text);
    }

    public function testToJson(): void
    {
        $metrics = $this->createTestMetrics();
        $report = new ProfileReport($metrics);

        $json = $report->toJson();

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('summary', $decoded);
        $this->assertArrayHasKey('metrics', $decoded);
    }

    public function testToArray(): void
    {
        $metrics = $this->createTestMetrics();
        $report = new ProfileReport($metrics);

        $array = $report->toArray();
        $this->assertArrayHasKey('summary', $array);
        $this->assertArrayHasKey('metrics', $array);

        $this->assertEquals(18, $array['summary']['totalOperations']); // 5 + 3 + 10
        $this->assertEquals(4.0, $array['summary']['totalTime']); // 1.5 + 0.5 + 2.0
    }

    public function testToStringAliasestoText(): void
    {
        $metrics = $this->createTestMetrics();
        $report = new ProfileReport($metrics);

        $this->assertEquals($report->toText(), (string) $report);
    }

    public function testEmptyReport(): void
    {
        $report = new ProfileReport([]);

        $text = $report->toText();
        $this->assertStringContainsString('No profiling data', $text);
    }
}
