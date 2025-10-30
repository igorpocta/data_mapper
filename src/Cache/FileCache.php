<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Cache;

use RuntimeException;

/**
 * File-based cache implementation for persistent storage
 * Stores cache entries as serialized PHP files
 * Perfect for production environments where metadata should persist across requests
 */
class FileCache implements CacheInterface
{
    private string $cacheDir;
    private int $defaultTtl;
    private string $extension;

    /**
     * @param string $cacheDir Directory where cache files will be stored
     * @param int $defaultTtl Default time to live in seconds (0 = no expiration)
     * @param string $extension File extension for cache files
     * @throws RuntimeException If cache directory cannot be created or is not writable
     */
    public function __construct(
        string $cacheDir,
        int $defaultTtl = 0,
        string $extension = '.cache.php'
    ) {
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR);
        $this->defaultTtl = $defaultTtl;
        $this->extension = $extension;

        $this->ensureCacheDirectoryExists();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return $default;
        }

        try {
            $content = file_get_contents($filename);
            if ($content === false) {
                return $default;
            }

            $data = @unserialize($content);

            // Check if data is valid and not expired
            if (!is_array($data) || !isset($data['value'], $data['expires'])) {
                $this->delete($key);
                return $default;
            }

            // Check expiration (0 = no expiration)
            if ($data['expires'] > 0 && $data['expires'] < time()) {
                $this->delete($key);
                return $default;
            }

            return $data['value'];
        } catch (\Throwable $e) {
            // If unserialization fails, delete corrupted cache file
            $this->delete($key);
            return $default;
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $filename = $this->getFilename($key);
        $ttl = $ttl ?? $this->defaultTtl;

        $data = [
            'key' => $key, // Store original key for retrieval
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
            'created' => time(),
        ];

        try {
            $content = serialize($data);

            // Ensure directory exists
            $dir = dirname($filename);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Write atomically using temporary file
            $tmpFile = $filename . '.' . uniqid('', true) . '.tmp';
            if (file_put_contents($tmpFile, $content, LOCK_EX) === false) {
                return false;
            }

            // Atomic rename
            if (!rename($tmpFile, $filename)) {
                @unlink($tmpFile);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function has(string $key): bool
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return false;
        }

        try {
            $content = file_get_contents($filename);
            if ($content === false) {
                return false;
            }

            $data = @unserialize($content);

            if (!is_array($data) || !isset($data['expires'])) {
                $this->delete($key);
                return false;
            }

            // Check expiration
            if ($data['expires'] > 0 && $data['expires'] < time()) {
                $this->delete($key);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->delete($key);
            return false;
        }
    }

    public function delete(string $key): bool
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return true;
        }

        return @unlink($filename);
    }

    public function clear(): bool
    {
        try {
            $this->deleteDirectory($this->cacheDir);
            $this->ensureCacheDirectoryExists();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get the number of cache files (for debugging/monitoring)
     */
    public function size(): int
    {
        return count($this->getAllCacheFiles());
    }

    /**
     * Get all cache keys (for debugging)
     * Note: Returns original keys by reading from cache files
     *
     * @return array<string>
     */
    public function keys(): array
    {
        $files = $this->getAllCacheFiles();
        /** @var array<string> $keys */
        $keys = [];

        foreach ($files as $file) {
            try {
                $content = file_get_contents($file);
                if ($content === false) {
                    continue;
                }

                $data = @unserialize($content);

                // For FileCache, we need to store the original key in the data
                // Since we can't reliably reverse the hash
                if (is_array($data) && isset($data['key']) && is_string($data['key'])) {
                    $keys[] = $data['key'];
                }
            } catch (\Throwable $e) {
                // Skip corrupted files
                continue;
            }
        }

        return $keys;
    }

    /**
     * Cleanup expired cache entries
     *
     * @return int Number of deleted entries
     */
    public function cleanup(): int
    {
        $files = $this->getAllCacheFiles();
        $deleted = 0;

        foreach ($files as $file) {
            try {
                $content = file_get_contents($file);
                if ($content === false) {
                    continue;
                }

                $data = @unserialize($content);

                if (!is_array($data) || !isset($data['expires'])) {
                    @unlink($file);
                    $deleted++;
                    continue;
                }

                // Delete expired entries
                if ($data['expires'] > 0 && $data['expires'] < time()) {
                    @unlink($file);
                    $deleted++;
                }
            } catch (\Throwable $e) {
                // Delete corrupted files
                @unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get cache statistics
     *
     * @return array{total: int, size_bytes: int, oldest: int|null, newest: int|null}
     */
    public function getStats(): array
    {
        $files = $this->getAllCacheFiles();
        $totalSize = 0;
        $oldest = null;
        $newest = null;

        foreach ($files as $file) {
            $size = filesize($file);
            if ($size !== false) {
                $totalSize += $size;
            }

            $mtime = filemtime($file);
            if ($mtime !== false) {
                if ($oldest === null || $mtime < $oldest) {
                    $oldest = $mtime;
                }
                if ($newest === null || $mtime > $newest) {
                    $newest = $mtime;
                }
            }
        }

        return [
            'total' => count($files),
            'size_bytes' => $totalSize,
            'oldest' => $oldest,
            'newest' => $newest,
        ];
    }

    /**
     * Get filename for a cache key
     */
    private function getFilename(string $key): string
    {
        // Create safe filename from key
        $safeKey = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);

        // Use hash to prevent too long filenames and collisions
        $hash = hash('sha256', $key);

        return $this->cacheDir . DIRECTORY_SEPARATOR . $safeKey . '_' . substr($hash, 0, 16) . $this->extension;
    }

    /**
     * Ensure cache directory exists and is writable
     *
     * @throws RuntimeException
     */
    private function ensureCacheDirectoryExists(): void
    {
        if (!is_dir($this->cacheDir)) {
            if (!@mkdir($this->cacheDir, 0755, true)) {
                throw new RuntimeException("Failed to create cache directory: {$this->cacheDir}");
            }
        }

        if (!is_writable($this->cacheDir)) {
            throw new RuntimeException("Cache directory is not writable: {$this->cacheDir}");
        }
    }

    /**
     * Get all cache files recursively
     *
     * @return array<string>
     */
    private function getAllCacheFiles(): array
    {
        if (!is_dir($this->cacheDir)) {
            return [];
        }

        /** @var array<string> $files */
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getPathname(), $this->extension)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Recursively delete directory and its contents
     *
     * @param string $dir
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($dir);
    }
}
