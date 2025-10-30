<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Cache;

/**
 * Cached metadata about a constructor parameter
 */
class ParameterMetadata
{
    /**
     * @param string $name Parameter name
     * @param string $jsonKey Key name in JSON/array
     * @param string $typeName Type name (int, string, MyClass, etc.)
     * @param bool $isNullable Whether parameter accepts null
     * @param bool $hasDefaultValue Whether parameter has default value
     * @param mixed $defaultValue Default value (if hasDefaultValue = true)
     * @param array<object> $attributes All attributes on this parameter
     * @param string|null $format DateTime format (if applicable)
     * @param string|null $timezone DateTime timezone (if applicable)
     * @param class-string|null $arrayOf Class name if parameter is array of objects
     * @param class-string|null $classType Class name if parameter is an object
     */
    public function __construct(
        public readonly string $name,
        public readonly string $jsonKey,
        public readonly string $typeName,
        public readonly bool $isNullable,
        public readonly bool $hasDefaultValue,
        public readonly mixed $defaultValue,
        public readonly array $attributes,
        public readonly ?string $format = null,
        public readonly ?string $timezone = null,
        public readonly ?string $arrayOf = null,
        public readonly ?string $classType = null
    ) {
    }
}
