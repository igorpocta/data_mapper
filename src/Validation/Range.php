<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Assert that numeric value is within range
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Range implements AssertInterface
{
    public function __construct(
        public readonly int|float|null $min = null,
        public readonly int|float|null $max = null,
        public readonly ?string $message = null
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        if ($value === null) {
            return null; // Use NotNull for null checks
        }

        if (!is_numeric($value)) {
            return "Property '{$propertyName}' must be numeric for Range validation";
        }

        $numValue = is_int($value) || is_float($value) ? $value : (float) $value;

        if ($this->min !== null && $numValue < $this->min) {
            return $this->message ?? "Property '{$propertyName}' must be at least {$this->min}";
        }

        if ($this->max !== null && $numValue > $this->max) {
            return $this->message ?? "Property '{$propertyName}' must be at most {$this->max}";
        }

        return null;
    }
}
