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
     * @param class-string|ArrayElementType|string|null $arrayOf Class name for array of objects, ArrayElementType enum for array of scalars (recommended), or type name string for backward compatibility when type is Array
     * @param class-string|null $classType Class name for nested objects
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $path = null,
        public readonly ?PropertyType $type = null,
        public readonly ArrayElementType|PropertyType|string|null $arrayOf = null,
        public readonly ?string $classType = null
    ) {
        if ($name !== null && $path !== null) {
            throw new \InvalidArgumentException('Cannot specify both $name and $path parameters');
        }

        // Validate arrayOf - don't allow complex types from PropertyType enum
        if ($arrayOf instanceof PropertyType) {
            $invalidTypes = [
                PropertyType::Array,
                PropertyType::DateTime,
                PropertyType::DateTimeImmutable,
                PropertyType::DateTimeInterface,
            ];

            if (in_array($arrayOf, $invalidTypes, true)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'arrayOf cannot be PropertyType::%s. Use ArrayElementType for scalar types or a class-string for objects.',
                        $arrayOf->name
                    )
                );
            }

            // For backward compatibility, still allow scalar PropertyType values
            // They will be converted to string in Denormalizer/Normalizer
        }
    }
}
