<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes;

use Attribute;

/**
 * Marks a property as the discriminator field for polymorphic mapping.
 *
 * This property's value is used to determine which concrete class
 * should be instantiated when deserializing polymorphic data.
 *
 * @example
 * abstract class Vehicle
 * {
 *     #[DiscriminatorProperty]
 *     protected string $type;
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class DiscriminatorProperty
{
}
