<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Cache;

use Redis;
use RuntimeException;

/**
 * Redis-based cache implementation for distributed caching
 * Supports both phpredis extension and Predis library
 * Perfect for production environments with multiple servers
 *
 * Requirements:
 * - phpredis extension (https://github.com/phpredis/phpredis)
 *   OR
 * - predis/predis package (composer require predis/predis)
 *
 * Example with phpredis:
 * ```php
 * $redis = new Redis();
 * $redis->connect('127.0.0.1', 6379);
 * $cache = new RedisCache($redis, prefix: 'mapper:', defaultTtl: 3600);
 * ```
 *
 * Example with Predis:
 * ```php
 * $redis = new Predis\Client(['host' => '127.0.0.1', 'port' => 6379]);
 * $cache = new RedisCache($redis, prefix: 'mapper:', defaultTtl: 3600);
 * ```
 *
 */
class RedisCache implements CacheInterface
{
    /** @var Redis|object */
    private object $redis;
    private string $prefix;
    private int $defaultTtl;
    private bool $isPredis;

    /**
     * @param Redis|object $redis Redis client instance (phpredis or Predis)
     * @param string $prefix Key prefix to avoid collisions
     * @param int $defaultTtl Default time to live in seconds (0 = no expiration)
     * @throws RuntimeException If Redis client is not valid
     */
    public function __construct(
        object $redis,
        string $prefix = 'mapper:',
        int $defaultTtl = 0
    ) {
        $this->validateRedisClient($redis);
        $this->redis = $redis;
        $this->prefix = $prefix;
        $this->defaultTtl = $defaultTtl;
        $this->isPredis = !($redis instanceof Redis);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $prefixedKey = $this->prefix . $key;

        try {
            $value = $this->redisGet($prefixedKey);

            if ($value === false || $value === null) {
                return $default;
            }

            if (!is_string($value)) {
                return $default;
            }

            $data = @unserialize($value);

            // unserialize returns false on error, but also when the serialized value was false
            // We need to check if it was actually an error
            if ($data === false && $value !== serialize(false)) {
                return $default;
            }

            return $data;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $prefixedKey = $this->prefix . $key;
        $ttl = $ttl ?? $this->defaultTtl;

        try {
            $serialized = serialize($value);

            if ($ttl > 0) {
                return $this->redisSetEx($prefixedKey, $ttl, $serialized);
            }

            return $this->redisSet($prefixedKey, $serialized);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function has(string $key): bool
    {
        $prefixedKey = $this->prefix . $key;

        try {
            return $this->redisExists($prefixedKey);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function delete(string $key): bool
    {
        $prefixedKey = $this->prefix . $key;

        try {
            $result = $this->redisDel($prefixedKey);
            return $result > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function clear(): bool
    {
        try {
            // Get all keys with our prefix
            $keys = $this->redisKeys($this->prefix . '*');

            if (empty($keys)) {
                return true;
            }

            // Delete all matching keys
            $this->redisDel(...$keys);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get the number of cache entries with this prefix
     */
    public function size(): int
    {
        try {
            $keys = $this->redisKeys($this->prefix . '*');
            return count($keys);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get all cache keys (without prefix)
     *
     * @return array<string>
     */
    public function keys(): array
    {
        try {
            $keys = $this->redisKeys($this->prefix . '*');
            $prefixLen = strlen($this->prefix);

            return array_map(
                fn(string $key) => substr($key, $prefixLen),
                $keys
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get cache statistics
     *
     * @return array{total: int, prefix: string, ttl_default: int, driver: string}
     */
    public function getStats(): array
    {
        return [
            'total' => $this->size(),
            'prefix' => $this->prefix,
            'ttl_default' => $this->defaultTtl,
            'driver' => $this->isPredis ? 'predis' : 'phpredis',
        ];
    }

    /**
     * Get TTL for a specific key (time to live in seconds)
     * Returns -1 if key has no expiry, -2 if key doesn't exist
     */
    public function getTtl(string $key): int
    {
        $prefixedKey = $this->prefix . $key;

        try {
            return $this->redisTtl($prefixedKey);
        } catch (\Throwable $e) {
            return -2;
        }
    }

    /**
     * Set expiration time for a key
     */
    public function expire(string $key, int $ttl): bool
    {
        $prefixedKey = $this->prefix . $key;

        try {
            return $this->redisExpire($prefixedKey, $ttl);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Persist a key (remove expiration)
     */
    public function persist(string $key): bool
    {
        $prefixedKey = $this->prefix . $key;

        try {
            return $this->redisPersist($prefixedKey);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Increment a numeric value
     */
    public function increment(string $key, int $value = 1): int|false
    {
        $prefixedKey = $this->prefix . $key;

        try {
            return $this->redisIncrBy($prefixedKey, $value);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Decrement a numeric value
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        $prefixedKey = $this->prefix . $key;

        try {
            return $this->redisDecrBy($prefixedKey, $value);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get Redis client info
     *
     * @return array<string, mixed>
     */
    public function info(): array
    {
        try {
            return $this->redisInfo();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Ping Redis server
     */
    public function ping(): bool
    {
        try {
            $result = $this->redisPing();
            return $result === true || $result === '+PONG' || $result === 'PONG';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Validate that provided client is a valid Redis instance
     *
     * @param object $redis
     * @throws RuntimeException
     */
    private function validateRedisClient(object $redis): void
    {
        if ($redis instanceof Redis) {
            return; // phpredis is valid
        }

        // Check if it's Predis
        $class = get_class($redis);
        if (str_contains($class, 'Predis') || str_contains($class, 'Client')) {
            // Basic duck-typing check for Predis
            if (method_exists($redis, 'get') && method_exists($redis, 'set')) {
                return;
            }
        }

        throw new RuntimeException(
            'Invalid Redis client. Expected Redis (phpredis) or Predis\Client instance.'
        );
    }

    // Wrapper methods for phpredis vs Predis compatibility

    private function redisGet(string $key): mixed
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            return $this->redis->get($key);
        }
        /** @phpstan-ignore method.notFound */
        return $this->redis->get($key);
    }

    private function redisSet(string $key, string $value): bool
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            $result = $this->redis->set($key, $value);
            return $result !== null && $result !== false;
        }
        /** @phpstan-ignore method.notFound */
        return $this->redis->set($key, $value);
    }

    private function redisSetEx(string $key, int $ttl, string $value): bool
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            $result = $this->redis->setex($key, $ttl, $value);
            return $result !== null && $result !== false;
        }
        /** @phpstan-ignore method.notFound */
        return $this->redis->setex($key, $ttl, $value);
    }

    private function redisExists(string $key): bool
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            return $this->redis->exists($key) > 0;
        }
        /** @phpstan-ignore method.notFound */
        return $this->redis->exists($key) > 0;
    }

    private function redisDel(string ...$keys): int
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            return $this->redis->del($keys);
        }
        /** @phpstan-ignore method.notFound */
        return $this->redis->del($keys);
    }

    /**
     * @return array<string>
     */
    private function redisKeys(string $pattern): array
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            $keys = $this->redis->keys($pattern);
            return is_array($keys) ? array_values(array_filter($keys, 'is_string')) : [];
        }
        /** @phpstan-ignore method.notFound */
        $result = $this->redis->keys($pattern);
        return is_array($result) ? array_values(array_filter($result, 'is_string')) : [];
    }

    private function redisTtl(string $key): int
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            return $this->redis->ttl($key);
        }
        /** @phpstan-ignore method.notFound */
        return $this->redis->ttl($key);
    }

    private function redisExpire(string $key, int $ttl): bool
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            return $this->redis->expire($key, $ttl) === 1;
        }
        /** @phpstan-ignore method.notFound */
        return $this->redis->expire($key, $ttl);
    }

    private function redisPersist(string $key): bool
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            return $this->redis->persist($key) === 1;
        }
        /** @phpstan-ignore method.notFound */
        return $this->redis->persist($key);
    }

    private function redisIncrBy(string $key, int $value): int|false
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            return $this->redis->incrby($key, $value);
        }
        /** @phpstan-ignore method.notFound */
        return $this->redis->incrBy($key, $value);
    }

    private function redisDecrBy(string $key, int $value): int|false
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            return $this->redis->decrby($key, $value);
        }
        /** @phpstan-ignore method.notFound */
        return $this->redis->decrBy($key, $value);
    }

    /**
     * @return array<string, mixed>
     */
    private function redisInfo(): array
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            $result = $this->redis->info();
            return is_array($result) ? $result : [];
        }
        /** @phpstan-ignore method.notFound */
        $result = $this->redis->info();
        return $result;
    }

    private function redisPing(): mixed
    {
        if ($this->isPredis) {
            /** @phpstan-ignore method.notFound */
            return $this->redis->ping();
        }
        /** @phpstan-ignore method.notFound */
        return $this->redis->ping();
    }
}
