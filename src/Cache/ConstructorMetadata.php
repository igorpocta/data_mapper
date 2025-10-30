<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Cache;

/**
 * Cached metadata about a class constructor
 */
class ConstructorMetadata
{
    /**
     * @param array<string, ParameterMetadata> $parameters Map of parameter name => metadata
     */
    public function __construct(
        public readonly array $parameters
    ) {
    }

    /**
     * Get parameter metadata by name
     */
    public function getParameter(string $name): ?ParameterMetadata
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * Get all parameter names
     *
     * @return array<string>
     */
    public function getParameterNames(): array
    {
        return array_keys($this->parameters);
    }

    /**
     * Check if constructor has parameters
     */
    public function hasParameters(): bool
    {
        return !empty($this->parameters);
    }
}
