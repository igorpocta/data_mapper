<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Suppresses all validation on a property.
 *
 * Ignores:
 * - All Assert attributes (#[NotBlank], #[Valid], #[Email], ...)
 * - All ConstraintInterface attributes
 * - The "required" check for uninitialized typed properties
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class SkipValidation
{
}
