<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Cache;

use Pocta\DataMapper\Attributes\MapFrom;

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
     * @param string|null $arrayOf Class name if parameter is array of objects, or scalar type name (int, string, etc.)
     * @param class-string|null $classType Class name if parameter is an object
     * @param MapFrom|null $mapFrom MapFrom attribute for object mapping
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
        public readonly ?string $classType = null,
        public readonly ?MapFrom $mapFrom = null
    ) {
    }
}
