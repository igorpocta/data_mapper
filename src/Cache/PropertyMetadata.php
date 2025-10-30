<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Cache;

use Pocta\DataMapper\Attributes\MapFrom;

/**
 * Cached metadata about a single property
 */
class PropertyMetadata
{
    /**
     * @param string $name Property name
     * @param string $jsonKey Key name in JSON/array
     * @param string $typeName Type name (int, string, MyClass, etc.)
     * @param bool $isNullable Whether property accepts null
     * @param bool $isPromoted Whether property is constructor-promoted
     * @param array<object> $attributes All attributes on this property
     * @param string|null $format DateTime format (if applicable)
     * @param string|null $timezone DateTime timezone (if applicable)
     * @param class-string|null $arrayOf Class name if property is array of objects
     * @param class-string|null $classType Class name if property is an object
     * @param MapFrom|null $mapFrom MapFrom attribute for object mapping
     */
    public function __construct(
        public readonly string $name,
        public readonly string $jsonKey,
        public readonly string $typeName,
        public readonly bool $isNullable,
        public readonly bool $isPromoted,
        public readonly array $attributes,
        public readonly ?string $format = null,
        public readonly ?string $timezone = null,
        public readonly ?string $arrayOf = null,
        public readonly ?string $classType = null,
        public readonly ?MapFrom $mapFrom = null
    ) {
    }
}
