<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Cache;

/**
 * Null cache implementation - no caching at all
 * Useful for debugging or when caching is not desired
 */
class NullCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function delete(string $key): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }
}
