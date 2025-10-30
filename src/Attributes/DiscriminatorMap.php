<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes;

use Attribute;

/**
 * Defines a discriminator map for polymorphic object mapping.
 *
 * This attribute is placed on a parent class/interface and defines:
 * - Which property contains the discriminator value (type indicator)
 * - The mapping between discriminator values and concrete classes
 *
 * @example
 * #[DiscriminatorMap(
 *     property: 'type',
 *     mapping: [
 *         'car' => Car::class,
 *         'bike' => Bike::class,
 *     ]
 * )]
 * abstract class Vehicle { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class DiscriminatorMap
{
    /**
     * @param string $property The property name that contains the discriminator value
     * @param array<string, class-string> $mapping Map of discriminator values to class names
     */
    public function __construct(
        public readonly string $property,
        public readonly array $mapping,
    ) {}
}
