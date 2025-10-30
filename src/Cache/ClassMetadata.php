<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Cache;

/**
 * Cached metadata about a class for fast property access
 * Stores all reflection information needed for mapping
 */
class ClassMetadata
{
    /**
     * @param string $className
     * @param array<string, PropertyMetadata> $properties Map of property name => metadata
     * @param ConstructorMetadata|null $constructor
     */
    public function __construct(
        public readonly string $className,
        public readonly array $properties,
        public readonly ?ConstructorMetadata $constructor = null
    ) {
    }

    /**
     * Get property metadata by name
     */
    public function getProperty(string $name): ?PropertyMetadata
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * Check if class has constructor
     */
    public function hasConstructor(): bool
    {
        return $this->constructor !== null;
    }

    /**
     * Get all property names
     *
     * @return array<string>
     */
    public function getPropertyNames(): array
    {
        return array_keys($this->properties);
    }
}
