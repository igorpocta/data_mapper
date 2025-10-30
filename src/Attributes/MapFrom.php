<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes;

use Attribute;

/**
 * Maps a property from a source object using property path or getter method
 *
 * Can be used to map from Doctrine entities, other DTOs, or any PHP object.
 *
 * Examples:
 *
 * // Map from getter method
 * #[MapFrom('getName')]
 * public string $name;
 *
 * // Map from property with getter (automatic)
 * #[MapFrom('email')]  // Will try getEmail(), email property, isEmail(), hasEmail()
 * public string $email;
 *
 * // Map from nested object
 * #[MapFrom('user.address.street')]
 * public string $street;
 *
 * // Map from collection by index
 * #[MapFrom('addresses[0].city')]
 * public string $firstCity;
 *
 * // Map from explicit method call
 * #[MapFrom('getFullName()')]
 * public string $fullName;
 *
 * // Combine with filters
 * #[MapFrom('createdAt')]
 * #[StringTrimFilter]
 * public string $created;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapFrom
{
    /**
     * @param string $path Property path or method name (e.g., "user.name", "getName()", "addresses[0].city")
     */
    public function __construct(
        public readonly string $path
    ) {
    }
}
