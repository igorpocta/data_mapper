<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Debug\Debugger;
use Pocta\DataMapper\Events\PreDenormalizeEvent;

class DebuggerTest extends TestCase
{
    public function testDebuggerLogsOperations(): void
    {
        $debugger = new Debugger(enabled: true);
        $mapper = new Mapper(debugger: $debugger);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, SimpleTestClass::class);

        $logs = $debugger->getLogs();
        $this->assertNotEmpty($logs);

        $operationLogs = $debugger->getLogsByType('operation');
        $this->assertCount(1, $operationLogs);
        $firstLog = reset($operationLogs);
        $this->assertIsArray($firstLog);
        $this->assertArrayHasKey('operation', $firstLog);
        /** @var array{operation: string} $firstLog */
        $this->assertEquals('fromArray', $firstLog['operation']);
    }

    public function testDebuggerLogsEvents(): void
    {
        $debugger = new Debugger(enabled: true);
        $mapper = new Mapper(debugger: $debugger);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, SimpleTestClass::class);

        $eventLogs = $debugger->getLogsByType('event');
        $this->assertNotEmpty($eventLogs);

        $eventStats = $debugger->getEventStats();
        $this->assertArrayHasKey(PreDenormalizeEvent::class, $eventStats);
    }

    public function testDebuggerCanBeDisabled(): void
    {
        $debugger = new Debugger(enabled: false);
        $mapper = new Mapper(debugger: $debugger);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, SimpleTestClass::class);

        $logs = $debugger->getLogs();
        $this->assertEmpty($logs);
    }

    public function testDebuggerClearLogs(): void
    {
        $debugger = new Debugger(enabled: true);
        $mapper = new Mapper(debugger: $debugger);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, SimpleTestClass::class);

        $this->assertNotEmpty($debugger->getLogs());

        $debugger->clearLogs();
        $this->assertEmpty($debugger->getLogs());
    }

    public function testDebuggerSummary(): void
    {
        $debugger = new Debugger(enabled: true);
        $mapper = new Mapper(debugger: $debugger);

        $data = ['name' => 'Test'];
        $mapper->fromArray($data, SimpleTestClass::class);

        $summary = $debugger->getSummary();
        $this->assertArrayHasKey('totalLogs', $summary);
        $this->assertArrayHasKey('operations', $summary);
        $this->assertArrayHasKey('events', $summary);
        $this->assertGreaterThan(0, $summary['totalLogs']);
    }

    public function testDebuggerWithDebugMode(): void
    {
        $debugger = new Debugger(enabled: true, debugMode: true);
        $this->assertTrue($debugger->isDebugMode());

        $debugger->setDebugMode(false);
        $this->assertFalse($debugger->isDebugMode());
    }

    public function testDebuggerGetterInMapper(): void
    {
        $debugger = new Debugger();
        $mapper = new Mapper(debugger: $debugger);

        $this->assertSame($debugger, $mapper->getDebugger());
    }

    public function testDebuggerNullInMapper(): void
    {
        $mapper = new Mapper();
        $this->assertNull($mapper->getDebugger());
    }
}

class SimpleTestClass
{
    public string $name;
}
