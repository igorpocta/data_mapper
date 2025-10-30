<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapProperty
{
    /**
     * @param string|null $name Custom JSON key name (mutually exclusive with $path)
     * @param string|null $path Property path with dot notation and array indexes (e.g., "user.address.street", "addresses[0].street")
     * @param PropertyType|null $type Type of the property
     * @param class-string|null $arrayOf Class name for array elements (when type is Array)
     * @param class-string|null $classType Class name for nested objects
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $path = null,
        public readonly ?PropertyType $type = null,
        public readonly ?string $arrayOf = null,
        public readonly ?string $classType = null
    ) {
        if ($name !== null && $path !== null) {
            throw new \InvalidArgumentException('Cannot specify both $name and $path parameters');
        }
    }
}
