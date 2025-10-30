<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes;

use Attribute;

/**
 * Attribute for mapping DateTime properties with custom format and timezone
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapDateTimeProperty
{
    /**
     * @param string|null $name Custom JSON key name (mutually exclusive with $path)
     * @param string|null $path Property path with dot notation and array indexes (e.g., "user.address.street", "addresses[0].street")
     * @param PropertyType|null $type DateTime type (DateTime, DateTimeImmutable, DateTimeInterface)
     * @param string|null $format Custom datetime format for parsing input (e.g., 'd/m/Y H:i')
     * @param string|null $timezone Timezone name (e.g., 'Europe/Prague', 'UTC')
     * @param class-string|null $arrayOf Class name for array elements (when type is Array)
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $path = null,
        public readonly ?PropertyType $type = null,
        public readonly ?string $format = null,
        public readonly ?string $timezone = null,
        public readonly ?string $arrayOf = null
    ) {
        if ($name !== null && $path !== null) {
            throw new \InvalidArgumentException('Cannot specify both $name and $path parameters');
        }
    }
}
