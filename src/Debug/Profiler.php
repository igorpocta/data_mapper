<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Debug;

/**
 * Profiler for measuring performance of mapper operations
 */
class Profiler
{
    /** @var array<string, array{count: int, totalTime: float, totalMemory: int, startTime?: float, startMemory?: int}> */
    private array $metrics = [];

    /** @var array<string> Stack of currently running operations */
    private array $stack = [];

    private bool $enabled = true;

    public function __construct(bool $enabled = true)
    {
        $this->enabled = $enabled;
    }

    /**
     * Enable or disable profiler
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * Check if profiler is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Start profiling an operation
     */
    public function start(string $operation): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!isset($this->metrics[$operation])) {
            $this->metrics[$operation] = [
                'count' => 0,
                'totalTime' => 0.0,
                'totalMemory' => 0,
            ];
        }

        $this->metrics[$operation]['startTime'] = microtime(true);
        $this->metrics[$operation]['startMemory'] = memory_get_usage(true);
        $this->stack[] = $operation;
    }

    /**
     * Stop profiling an operation
     */
    public function stop(string $operation): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!isset($this->metrics[$operation]['startTime'], $this->metrics[$operation]['startMemory'])) {
            return; // Operation was not started
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $duration = $endTime - $this->metrics[$operation]['startTime'];
        $memoryUsed = max(0, $endMemory - $this->metrics[$operation]['startMemory']);

        $this->metrics[$operation]['count']++;
        $this->metrics[$operation]['totalTime'] += $duration;
        $this->metrics[$operation]['totalMemory'] += $memoryUsed;

        unset($this->metrics[$operation]['startTime']);
        unset($this->metrics[$operation]['startMemory']);

        // Remove from stack
        $key = array_search($operation, $this->stack, true);
        if ($key !== false) {
            array_splice($this->stack, (int) $key, 1);
        }
    }

    /**
     * Profile a callable
     *
     * @template T
     * @param string $operation Operation name
     * @param callable(): T $callable
     * @return T
     */
    public function profile(string $operation, callable $callable): mixed
    {
        $this->start($operation);
        try {
            return $callable();
        } finally {
            $this->stop($operation);
        }
    }

    /**
     * Get metrics for a specific operation
     *
     * @return array{count: int, totalTime: float, totalMemory: int, avgTime: float, avgMemory: float}|null
     */
    public function getMetrics(string $operation): ?array
    {
        if (!isset($this->metrics[$operation])) {
            return null;
        }

        $data = $this->metrics[$operation];
        $count = $data['count'];

        return [
            'count' => $count,
            'totalTime' => $data['totalTime'],
            'totalMemory' => $data['totalMemory'],
            'avgTime' => $count > 0 ? $data['totalTime'] / $count : 0,
            'avgMemory' => $count > 0 ? $data['totalMemory'] / $count : 0,
        ];
    }

    /**
     * Get all metrics
     *
     * @return array<string, array{count: int, totalTime: float, totalMemory: int, avgTime: float, avgMemory: float}>
     */
    public function getAllMetrics(): array
    {
        $result = [];
        foreach (array_keys($this->metrics) as $operation) {
            $metrics = $this->getMetrics($operation);
            if ($metrics !== null) {
                $result[$operation] = $metrics;
            }
        }
        return $result;
    }

    /**
     * Get current stack of running operations
     *
     * @return array<string>
     */
    public function getStack(): array
    {
        return $this->stack;
    }

    /**
     * Clear all metrics
     */
    public function clear(): void
    {
        $this->metrics = [];
        $this->stack = [];
    }

    /**
     * Get a formatted report
     */
    public function getReport(): ProfileReport
    {
        return new ProfileReport($this->getAllMetrics());
    }

    /**
     * Get summary statistics
     *
     * @return array{totalOperations: int, totalTime: float, totalMemory: int, peakMemory: int}
     */
    public function getSummary(): array
    {
        $totalOps = 0;
        $totalTime = 0.0;
        $totalMemory = 0;

        foreach ($this->metrics as $data) {
            $totalOps += $data['count'];
            $totalTime += $data['totalTime'];
            $totalMemory += $data['totalMemory'];
        }

        return [
            'totalOperations' => $totalOps,
            'totalTime' => $totalTime,
            'totalMemory' => $totalMemory,
            'peakMemory' => memory_get_peak_usage(true),
        ];
    }
}
