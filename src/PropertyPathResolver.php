<?php

declare(strict_types=1);

namespace Pocta\DataMapper;

use InvalidArgumentException;

/**
 * Resolves property paths with support for:
 * - Dot notation (e.g., "user.address.street")
 * - Array indexes (e.g., "addresses[0].street")
 * - Mixed notation (e.g., "user.addresses[0].streetName")
 */
class PropertyPathResolver
{
    /**
     * Resolves a property path in the given data array
     *
     * @param array<string, mixed> $data Input data
     * @param string $path Property path (e.g., "user.address.street", "addresses[0].street")
     * @return mixed Resolved value or null if path doesn't exist
     */
    public static function resolve(array $data, string $path): mixed
    {
        if ($path === '') {
            return null;
        }

        $segments = self::parsePath($path);
        $current = $data;

        foreach ($segments as $segment) {
            if ($segment['type'] === 'property') {
                if (!isset($segment['key'])) {
                    return null;
                }
                if (!is_array($current) || !array_key_exists($segment['key'], $current)) {
                    return null;
                }
                $current = $current[$segment['key']];
            } elseif ($segment['type'] === 'index') {
                if (!isset($segment['index'])) {
                    return null;
                }
                if (!is_array($current) || !isset($current[$segment['index']])) {
                    return null;
                }
                $current = $current[$segment['index']];
            }
        }

        return $current;
    }

    /**
     * Checks if a property path exists in the given data array
     *
     * @param array<string, mixed> $data Input data
     * @param string $path Property path
     * @return bool True if path exists, false otherwise
     */
    public static function exists(array $data, string $path): bool
    {
        if ($path === '') {
            return false;
        }

        $segments = self::parsePath($path);
        $current = $data;

        foreach ($segments as $segment) {
            if ($segment['type'] === 'property') {
                if (!isset($segment['key'])) {
                    return false;
                }
                if (!is_array($current) || !array_key_exists($segment['key'], $current)) {
                    return false;
                }
                $current = $current[$segment['key']];
            } elseif ($segment['type'] === 'index') {
                if (!isset($segment['index'])) {
                    return false;
                }
                if (!is_array($current) || !isset($current[$segment['index']])) {
                    return false;
                }
                $current = $current[$segment['index']];
            }
        }

        return true;
    }

    /**
     * Parses a property path into segments
     *
     * Examples:
     * - "user.address.street" => [
     *     ['type' => 'property', 'key' => 'user'],
     *     ['type' => 'property', 'key' => 'address'],
     *     ['type' => 'property', 'key' => 'street']
     *   ]
     * - "addresses[0].street" => [
     *     ['type' => 'property', 'key' => 'addresses'],
     *     ['type' => 'index', 'index' => 0],
     *     ['type' => 'property', 'key' => 'street']
     *   ]
     *
     * @param string $path Property path
     * @return array<int, array{type: string, key?: string, index?: int}> Parsed segments
     * @throws InvalidArgumentException If path syntax is invalid
     */
    private static function parsePath(string $path): array
    {
        $segments = [];
        $length = strlen($path);
        $i = 0;

        while ($i < $length) {
            // Read property name
            $propertyName = '';
            while ($i < $length && $path[$i] !== '.' && $path[$i] !== '[') {
                $propertyName .= $path[$i];
                $i++;
            }

            if ($propertyName !== '') {
                $segments[] = ['type' => 'property', 'key' => $propertyName];
            }

            // Check for array index
            if ($i < $length && $path[$i] === '[') {
                $i++; // Skip '['
                $indexStr = '';
                while ($i < $length && $path[$i] !== ']') {
                    $indexStr .= $path[$i];
                    $i++;
                }

                if ($i >= $length || $path[$i] !== ']') {
                    throw new InvalidArgumentException("Invalid path syntax: missing closing bracket in '{$path}'");
                }

                $i++; // Skip ']'

                if (!is_numeric($indexStr)) {
                    throw new InvalidArgumentException("Invalid path syntax: array index must be numeric in '{$path}'");
                }

                $segments[] = ['type' => 'index', 'index' => (int) $indexStr];
            }

            // Skip dot separator
            if ($i < $length && $path[$i] === '.') {
                $i++;
            }
        }

        if (empty($segments)) {
            throw new InvalidArgumentException("Invalid path syntax: empty path");
        }

        return $segments;
    }

    /**
     * Validates a property path syntax without resolving it
     *
     * @param string $path Property path
     * @return bool True if path syntax is valid
     */
    public static function isValidPath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        try {
            self::parsePath($path);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
