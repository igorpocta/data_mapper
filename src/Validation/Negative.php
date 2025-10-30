<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is negative (< 0)
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Negative implements AssertInterface
{
    public function __construct(
        public readonly ?string $message = null
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            return $this->message ?? "Property '{$propertyName}' must be numeric";
        }

        if ($value < 0) {
            return null;
        }

        return $this->message ?? "Property '{$propertyName}' must be negative";
    }
}
