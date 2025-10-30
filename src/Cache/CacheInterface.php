<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Cache;

/**
 * Simple cache interface for storing reflection metadata
 * Compatible with PSR-16 Simple Cache subset
 */
interface CacheInterface
{
    /**
     * Fetches a value from the cache
     *
     * @param string $key The unique key of this item in the cache
     * @param mixed $default Default value to return if the key does not exist
     * @return mixed The value of the item from the cache, or $default in case of cache miss
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Persists data in the cache, uniquely referenced by a key
     *
     * @param string $key The key of the item to store
     * @param mixed $value The value of the item to store
     * @param int|null $ttl Optional time to live in seconds. null = forever
     * @return bool True on success, false on failure
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Determines whether an item is present in the cache
     *
     * @param string $key The cache item key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Deletes an item from the cache by its unique key
     *
     * @param string $key The unique cache key
     * @return bool True if the item was successfully removed, false otherwise
     */
    public function delete(string $key): bool;

    /**
     * Wipes clean the entire cache
     *
     * @return bool True on success, false on failure
     */
    public function clear(): bool;
}
