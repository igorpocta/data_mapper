<?php

declare(strict_types=1);

namespace Pocta\DataMapper;

use InvalidArgumentException;

/**
 * Resolves property paths from objects with support for:
 * - Dot notation (e.g., "user.address.street")
 * - Array indexes (e.g., "addresses[0].street")
 * - Mixed notation (e.g., "user.addresses[0].streetName")
 * - Getter methods (e.g., "user.getName()" or automatic "user.name" -> getName())
 * - Public properties
 * - Doctrine entity relationships
 */
class ObjectPathResolver
{
    /**
     * Resolves a property path from the given object
     *
     * @param object $object Source object (Entity, DTO, etc.)
     * @param string $path Property path (e.g., "user.address.street", "addresses[0].street")
     * @return mixed Resolved value or null if path doesn't exist
     */
    public static function resolve(object $object, string $path): mixed
    {
        if ($path === '') {
            return null;
        }

        $segments = self::parsePath($path);
        $current = $object;

        foreach ($segments as $segment) {
            if ($segment['type'] === 'property') {
                if (!isset($segment['key'])) {
                    return null;
                }

                $current = self::getPropertyValue($current, $segment['key']);

                if ($current === null) {
                    return null;
                }
            } elseif ($segment['type'] === 'index') {
                if (!isset($segment['index'])) {
                    return null;
                }

                // Convert to array-like access
                if (is_array($current)) {
                    if (!isset($current[$segment['index']])) {
                        return null;
                    }
                    $current = $current[$segment['index']];
                } elseif ($current instanceof \ArrayAccess) {
                    if (!isset($current[$segment['index']])) {
                        return null;
                    }
                    $current = $current[$segment['index']];
                } elseif ($current instanceof \Traversable) {
                    // For Doctrine Collections and other iterables
                    $current = self::getByIndex($current, $segment['index']);
                    if ($current === null) {
                        return null;
                    }
                } else {
                    return null;
                }
            } elseif ($segment['type'] === 'method') {
                if (!isset($segment['method'])) {
                    return null;
                }

                $current = self::callMethod($current, $segment['method']);

                if ($current === null) {
                    return null;
                }
            }
        }

        return $current;
    }

    /**
     * Checks if a property path exists in the given object
     *
     * @param object $object Source object
     * @param string $path Property path
     * @return bool True if path exists, false otherwise
     */
    public static function exists(object $object, string $path): bool
    {
        if ($path === '') {
            return false;
        }

        try {
            $value = self::resolve($object, $path);
            return $value !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get property value from object using various access methods
     *
     * Priority:
     * 1. Getter method (getName(), name())
     * 2. Public property
     * 3. Is method for boolean (isActive())
     * 4. Has method (hasPermission())
     *
     * @param mixed $object Source object
     * @param string $property Property name
     * @return mixed Property value or null if not accessible
     */
    private static function getPropertyValue(mixed $object, string $property): mixed
    {
        if (!is_object($object)) {
            return null;
        }

        // Try getter: getName()
        $getter = 'get' . ucfirst($property);
        if (method_exists($object, $getter) && is_callable([$object, $getter])) {
            return $object->$getter();
        }

        // Try direct method call: name()
        if (method_exists($object, $property) && is_callable([$object, $property])) {
            return $object->$property();
        }

        // Try is method for booleans: isActive()
        $isMethod = 'is' . ucfirst($property);
        if (method_exists($object, $isMethod) && is_callable([$object, $isMethod])) {
            return $object->$isMethod();
        }

        // Try has method: hasPermission()
        $hasMethod = 'has' . ucfirst($property);
        if (method_exists($object, $hasMethod) && is_callable([$object, $hasMethod])) {
            return $object->$hasMethod();
        }

        // Try public property
        if (property_exists($object, $property)) {
            $reflection = new \ReflectionProperty($object, $property);
            if ($reflection->isPublic()) {
                return $object->$property;
            }
        }

        return null;
    }

    /**
     * Call a method on the object
     *
     * @param mixed $object Source object
     * @param string $method Method name (with or without parentheses)
     * @return mixed Method return value or null
     */
    private static function callMethod(mixed $object, string $method): mixed
    {
        if (!is_object($object)) {
            return null;
        }

        // Remove parentheses if present
        $method = rtrim($method, '()');

        if (method_exists($object, $method) && is_callable([$object, $method])) {
            return $object->$method();
        }

        return null;
    }

    /**
     * Get element from iterable by index
     *
     * @param \Traversable<mixed> $iterable
     * @param int $index
     * @return mixed Element at index or null
     */
    private static function getByIndex(\Traversable $iterable, int $index): mixed
    {
        $i = 0;
        foreach ($iterable as $item) {
            if ($i === $index) {
                return $item;
            }
            $i++;
        }
        return null;
    }

    /**
     * Parse a property path into segments
     *
     * Examples:
     * - "user.name" -> [['type' => 'property', 'key' => 'user'], ['type' => 'property', 'key' => 'name']]
     * - "addresses[0]" -> [['type' => 'property', 'key' => 'addresses'], ['type' => 'index', 'index' => 0]]
     * - "user.getName()" -> [['type' => 'property', 'key' => 'user'], ['type' => 'method', 'method' => 'getName']]
     *
     * @param string $path Property path
     * @return array<array{type: string, key?: string, index?: int, method?: string}> Array of segments
     */
    private static function parsePath(string $path): array
    {
        $segments = [];
        $parts = explode('.', $path);

        foreach ($parts as $part) {
            // Check for method call: getName()
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\(\)$/', $part, $matches)) {
                $segments[] = [
                    'type' => 'method',
                    'method' => $matches[1],
                ];
                continue;
            }

            // Check for array index: addresses[0]
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\[(\d+)\]$/', $part, $matches)) {
                $segments[] = [
                    'type' => 'property',
                    'key' => $matches[1],
                ];
                $segments[] = [
                    'type' => 'index',
                    'index' => (int) $matches[2],
                ];
                continue;
            }

            // Regular property
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $part)) {
                $segments[] = [
                    'type' => 'property',
                    'key' => $part,
                ];
                continue;
            }

            throw new InvalidArgumentException("Invalid path segment: {$part}");
        }

        return $segments;
    }

    /**
     * Validate if a path has valid syntax
     *
     * @param string $path Property path
     * @return bool True if path is valid
     */
    public static function isValidPath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        try {
            self::parsePath($path);
            return true;
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }
}
