<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is less than or equal to a given value
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class LessThanOrEqual implements AssertInterface
{
    public function __construct(
        public readonly int|float $value,
        public readonly ?string $message = null
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            return $this->message ?? "Property '{$propertyName}' must be numeric to compare";
        }

        if ($value <= $this->value) {
            return null;
        }

        return $this->message ?? "Property '{$propertyName}' must be less than or equal to {$this->value}";
    }
}
