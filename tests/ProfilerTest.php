<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Debug\Profiler;
use Pocta\DataMapper\Debug\ProfileReport;

class ProfilerTest extends TestCase
{
    public function testProfilerMeasuresOperations(): void
    {
        $profiler = new Profiler(enabled: true);
        $mapper = new Mapper(profiler: $profiler);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, ProfilerTestClass::class);

        $metrics = $profiler->getMetrics('fromArray');
        $this->assertNotNull($metrics);
        $this->assertEquals(1, $metrics['count']);
        $this->assertGreaterThan(0, $metrics['totalTime']);
    }

    public function testProfilerWithMultipleCalls(): void
    {
        $profiler = new Profiler(enabled: true);
        $mapper = new Mapper(profiler: $profiler);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, ProfilerTestClass::class);
        $mapper->fromArray($data, ProfilerTestClass::class);
        $mapper->fromArray($data, ProfilerTestClass::class);

        $metrics = $profiler->getMetrics('fromArray');
        $this->assertNotNull($metrics);
        $this->assertEquals(3, $metrics['count']);
        $this->assertGreaterThan(0, $metrics['avgTime']);
    }

    public function testProfilerCanBeDisabled(): void
    {
        $profiler = new Profiler(enabled: false);
        $mapper = new Mapper(profiler: $profiler);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, ProfilerTestClass::class);

        $metrics = $profiler->getAllMetrics();
        $this->assertEmpty($metrics);
    }

    public function testProfilerClear(): void
    {
        $profiler = new Profiler(enabled: true);
        $mapper = new Mapper(profiler: $profiler);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, ProfilerTestClass::class);

        $this->assertNotEmpty($profiler->getAllMetrics());

        $profiler->clear();
        $this->assertEmpty($profiler->getAllMetrics());
    }

    public function testProfilerGetAllMetrics(): void
    {
        $profiler = new Profiler(enabled: true);
        $mapper = new Mapper(profiler: $profiler);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, ProfilerTestClass::class);
        $obj = $mapper->fromArray($data, ProfilerTestClass::class);
        $mapper->toArray($obj);

        $metrics = $profiler->getAllMetrics();
        $this->assertArrayHasKey('fromArray', $metrics);
        $this->assertArrayHasKey('toArray', $metrics);
    }

    public function testProfilerGetSummary(): void
    {
        $profiler = new Profiler(enabled: true);
        $mapper = new Mapper(profiler: $profiler);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, ProfilerTestClass::class);

        $summary = $profiler->getSummary();
        $this->assertArrayHasKey('totalOperations', $summary);
        $this->assertArrayHasKey('totalTime', $summary);
        $this->assertArrayHasKey('totalMemory', $summary);
        $this->assertArrayHasKey('peakMemory', $summary);
        $this->assertGreaterThan(0, $summary['totalOperations']);
    }

    public function testProfilerProfile(): void
    {
        $profiler = new Profiler(enabled: true);

        $result = $profiler->profile('test_operation', function () {
            usleep(1000); // Sleep 1ms
            return 'test_result';
        });

        $this->assertEquals('test_result', $result);

        $metrics = $profiler->getMetrics('test_operation');
        $this->assertNotNull($metrics);
        $this->assertEquals(1, $metrics['count']);
        $this->assertGreaterThan(0, $metrics['totalTime']);
    }

    public function testProfilerGetReport(): void
    {
        $profiler = new Profiler(enabled: true);
        $mapper = new Mapper(profiler: $profiler);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, ProfilerTestClass::class);

        $report = $profiler->getReport();
        $this->assertInstanceOf(ProfileReport::class, $report);
    }

    public function testProfilerGetterInMapper(): void
    {
        $profiler = new Profiler();
        $mapper = new Mapper(profiler: $profiler);

        $this->assertSame($profiler, $mapper->getProfiler());
    }

    public function testProfilerNullInMapper(): void
    {
        $mapper = new Mapper();
        $this->assertNull($mapper->getProfiler());
    }

    public function testProfilerTracksNormalization(): void
    {
        $profiler = new Profiler(enabled: true);
        $mapper = new Mapper(profiler: $profiler);

        $obj = new ProfilerTestClass();
        $obj->name = 'Test';
        $mapper->toArray($obj);

        $metrics = $profiler->getMetrics('normalize');
        $this->assertNotNull($metrics);
        $this->assertEquals(1, $metrics['count']);
    }

    public function testProfilerTracksDenormalization(): void
    {
        $profiler = new Profiler(enabled: true);
        $mapper = new Mapper(profiler: $profiler);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, ProfilerTestClass::class);

        $metrics = $profiler->getMetrics('denormalize');
        $this->assertNotNull($metrics);
        $this->assertEquals(1, $metrics['count']);
    }
}

class ProfilerTestClass
{
    public string $name;
}
