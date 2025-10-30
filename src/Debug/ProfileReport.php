<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Debug;

/**
 * Profile report containing formatted profiling data
 */
class ProfileReport
{
    /**
     * @param array<string, array{count: int, totalTime: float, totalMemory: int, avgTime: float, avgMemory: float}> $metrics
     */
    public function __construct(
        private readonly array $metrics
    ) {
    }

    /**
     * Get all metrics
     *
     * @return array<string, array{count: int, totalTime: float, totalMemory: int, avgTime: float, avgMemory: float}>
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Get metrics sorted by total time (descending)
     *
     * @return array<string, array{count: int, totalTime: float, totalMemory: int, avgTime: float, avgMemory: float}>
     */
    public function getSortedByTime(): array
    {
        $sorted = $this->metrics;
        uasort($sorted, fn($a, $b) => $b['totalTime'] <=> $a['totalTime']);
        return $sorted;
    }

    /**
     * Get metrics sorted by total memory (descending)
     *
     * @return array<string, array{count: int, totalTime: float, totalMemory: int, avgTime: float, avgMemory: float}>
     */
    public function getSortedByMemory(): array
    {
        $sorted = $this->metrics;
        uasort($sorted, fn($a, $b) => $b['totalMemory'] <=> $a['totalMemory']);
        return $sorted;
    }

    /**
     * Get metrics sorted by call count (descending)
     *
     * @return array<string, array{count: int, totalTime: float, totalMemory: int, avgTime: float, avgMemory: float}>
     */
    public function getSortedByCount(): array
    {
        $sorted = $this->metrics;
        uasort($sorted, fn($a, $b) => $b['count'] <=> $a['count']);
        return $sorted;
    }

    /**
     * Get top N operations by time
     *
     * @return array<string, array{count: int, totalTime: float, totalMemory: int, avgTime: float, avgMemory: float}>
     */
    public function getTopByTime(int $limit = 10): array
    {
        return array_slice($this->getSortedByTime(), 0, $limit, true);
    }

    /**
     * Get top N operations by memory
     *
     * @return array<string, array{count: int, totalTime: float, totalMemory: int, avgTime: float, avgMemory: float}>
     */
    public function getTopByMemory(int $limit = 10): array
    {
        return array_slice($this->getSortedByMemory(), 0, $limit, true);
    }

    /**
     * Format time in human-readable format
     */
    private function formatTime(float $seconds): string
    {
        if ($seconds < 0.001) {
            return number_format($seconds * 1000000, 2) . ' μs';
        }
        if ($seconds < 1) {
            return number_format($seconds * 1000, 2) . ' ms';
        }
        return number_format($seconds, 3) . ' s';
    }

    /**
     * Format memory in human-readable format
     */
    private function formatMemory(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $powInt = (int) $pow;
        $bytes /= (1 << (10 * $powInt));
        return round($bytes, 2) . ' ' . $units[$powInt];
    }

    /**
     * Get a formatted text report
     */
    public function toText(): string
    {
        if (empty($this->metrics)) {
            return "No profiling data available.\n";
        }

        $output = "=== PROFILING REPORT ===\n\n";

        // Summary
        $totalOps = array_sum(array_column($this->metrics, 'count'));
        $totalTime = array_sum(array_column($this->metrics, 'totalTime'));
        $totalMemory = array_sum(array_column($this->metrics, 'totalMemory'));

        $output .= "Summary:\n";
        $output .= sprintf("  Total Operations: %d\n", $totalOps);
        $output .= sprintf("  Total Time: %s\n", $this->formatTime($totalTime));
        $output .= sprintf("  Total Memory: %s\n", $this->formatMemory($totalMemory));
        $output .= sprintf("  Peak Memory: %s\n\n", $this->formatMemory(memory_get_peak_usage(true)));

        // Detailed metrics
        $output .= "Detailed Metrics:\n";
        $output .= str_repeat('-', 100) . "\n";
        $output .= sprintf(
            "%-40s | %8s | %15s | %15s | %15s\n",
            'Operation',
            'Count',
            'Total Time',
            'Avg Time',
            'Avg Memory'
        );
        $output .= str_repeat('-', 100) . "\n";

        foreach ($this->getSortedByTime() as $operation => $data) {
            $output .= sprintf(
                "%-40s | %8d | %15s | %15s | %15s\n",
                $operation,
                $data['count'],
                $this->formatTime($data['totalTime']),
                $this->formatTime($data['avgTime']),
                $this->formatMemory((int)$data['avgMemory'])
            );
        }

        $output .= str_repeat('-', 100) . "\n";

        return $output;
    }

    /**
     * Get report as JSON
     *
     * @throws \JsonException
     */
    public function toJson(): string
    {
        return json_encode([
            'summary' => [
                'totalOperations' => array_sum(array_column($this->metrics, 'count')),
                'totalTime' => array_sum(array_column($this->metrics, 'totalTime')),
                'totalMemory' => array_sum(array_column($this->metrics, 'totalMemory')),
                'peakMemory' => memory_get_peak_usage(true),
            ],
            'metrics' => $this->metrics,
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    /**
     * Get report as array
     *
     * @return array{summary: array{totalOperations: int, totalTime: float, totalMemory: int, peakMemory: int}, metrics: array<string, array{count: int, totalTime: float, totalMemory: int, avgTime: float, avgMemory: float}>}
     */
    public function toArray(): array
    {
        return [
            'summary' => [
                'totalOperations' => array_sum(array_column($this->metrics, 'count')),
                'totalTime' => array_sum(array_column($this->metrics, 'totalTime')),
                'totalMemory' => array_sum(array_column($this->metrics, 'totalMemory')),
                'peakMemory' => memory_get_peak_usage(true),
            ],
            'metrics' => $this->metrics,
        ];
    }

    /**
     * Convert to string (alias for toText)
     */
    public function __toString(): string
    {
        return $this->toText();
    }
}
