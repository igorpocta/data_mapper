<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Debug;

use Pocta\DataMapper\Events\EventInterface;

/**
 * Debugger for logging and debugging mapper operations
 */
class Debugger
{
    /** @var array<array<string, mixed>> */
    private array $logs = [];

    /** @var array<string, int> */
    private array $eventCounts = [];

    private bool $enabled = true;
    private bool $debugMode = false;

    /** @var resource|false */
    private $outputStream;

    public function __construct(bool $enabled = true, bool $debugMode = false)
    {
        $this->enabled = $enabled;
        $this->debugMode = $debugMode;
        $this->outputStream = fopen('php://stderr', 'w');
    }

    /**
     * Enable or disable debugger
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Check if debugger is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable or disable debug mode (verbose output)
     */
    public function setDebugMode(bool $debugMode): void
    {
        $this->debugMode = $debugMode;
    }

    /**
     * Check if debug mode is enabled
     */
    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    /**
     * Set output stream for debug messages
     *
     * @param resource|false $stream
     */
    public function setOutputStream($stream): void
    {
        $this->outputStream = $stream;
    }

    /**
     * Log a mapper operation
     *
     * @param string $operation Operation name (fromArray, toArray, fromJson, toJson)
     * @param mixed $data Data being processed
     * @param string|null $className Target class name if applicable
     */
    public function logOperation(string $operation, mixed $data, ?string $className = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $logEntry = [
            'type' => 'operation',
            'operation' => $operation,
            'className' => $className,
            'dataType' => get_debug_type($data),
            'timestamp' => microtime(true),
        ];

        if ($this->debugMode) {
            $logEntry['data'] = $data;
        }

        $this->logs[] = $logEntry;

        if ($this->debugMode && $this->outputStream) {
            $this->writeDebug("Operation: {$operation}" . ($className ? " -> {$className}" : ""));
            if (is_array($data) || is_object($data)) {
                $this->writeDebug("Data: " . print_r($data, true));
            }
        }
    }

    /**
     * Log an event dispatch
     *
     * @param EventInterface $event
     */
    public function logEvent(EventInterface $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $eventClass = get_class($event);

        $this->logs[] = [
            'type' => 'event',
            'event' => $eventClass,
            'timestamp' => microtime(true),
        ];

        if (!isset($this->eventCounts[$eventClass])) {
            $this->eventCounts[$eventClass] = 0;
        }
        $this->eventCounts[$eventClass]++;

        if ($this->debugMode && $this->outputStream) {
            $this->writeDebug("Event: {$eventClass}");
        }
    }

    /**
     * Log metadata information
     *
     * @param string $className
     * @param array<string, mixed> $metadata
     * @param bool $fromCache
     */
    public function logMetadata(string $className, array $metadata, bool $fromCache): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->logs[] = [
            'type' => 'metadata',
            'className' => $className,
            'fromCache' => $fromCache,
            'propertyCount' => count($metadata),
            'timestamp' => microtime(true),
        ];

        if ($this->debugMode && $this->outputStream) {
            $cacheStatus = $fromCache ? 'CACHE HIT' : 'CACHE MISS';
            $propertyCount = count($metadata);
            $this->writeDebug("Metadata [{$cacheStatus}]: {$className} ({$propertyCount} properties)");
        }
    }

    /**
     * Log cache operation
     *
     * @param string $operation Operation type (hit, miss, clear, store)
     * @param string|null $className Class name if applicable
     */
    public function logCache(string $operation, ?string $className = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->logs[] = [
            'type' => 'cache',
            'operation' => $operation,
            'className' => $className,
            'timestamp' => microtime(true),
        ];

        if ($this->debugMode && $this->outputStream) {
            $this->writeDebug("Cache {$operation}" . ($className ? ": {$className}" : ""));
        }
    }

    /**
     * Get all logs
     *
     * @return array<array<string, mixed>>
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Get event statistics
     *
     * @return array<string, int>
     */
    public function getEventStats(): array
    {
        return $this->eventCounts;
    }

    /**
     * Get logs filtered by type
     *
     * @param string $type Log type (operation, event, metadata, cache)
     * @return array<array<string, mixed>>
     */
    public function getLogsByType(string $type): array
    {
        return array_filter($this->logs, fn($log) => $log['type'] === $type);
    }

    /**
     * Clear all logs
     */
    public function clearLogs(): void
    {
        $this->logs = [];
        $this->eventCounts = [];
    }

    /**
     * Get a summary of debug information
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $operations = $this->getLogsByType('operation');
        $events = $this->getLogsByType('event');
        $metadata = $this->getLogsByType('metadata');
        $cache = $this->getLogsByType('cache');

        $cacheHits = count(array_filter($cache, fn($log) => isset($log['operation']) && $log['operation'] === 'hit'));
        $cacheMisses = count(array_filter($cache, fn($log) => isset($log['operation']) && $log['operation'] === 'miss'));

        $totalCacheOps = $cacheHits + $cacheMisses;
        $cacheHitRatio = $totalCacheOps > 0
            ? round($cacheHits / $totalCacheOps * 100, 2)
            : 0.0;

        return [
            'totalLogs' => count($this->logs),
            'operations' => count($operations),
            'events' => count($events),
            'eventTypes' => count($this->eventCounts),
            'metadataLoads' => count($metadata),
            'cacheHits' => $cacheHits,
            'cacheMisses' => $cacheMisses,
            'cacheHitRatio' => $cacheHitRatio,
        ];
    }

    /**
     * Write debug message to output stream
     */
    private function writeDebug(string $message): void
    {
        if ($this->outputStream) {
            fwrite($this->outputStream, "[DEBUG] {$message}\n");
        }
    }
}
