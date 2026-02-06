<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is divisible by a given number
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class DivisibleBy implements AssertInterface
{
    public function __construct(
        public readonly int|float $value,
        public readonly ?string $message = null,
        /** @var array<string> */
        public readonly array $groups = ['Default'],
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

        if ($this->value == 0) {
            return $this->message ?? "Division by zero is not allowed";
        }

        // Check if divisible (remainder is 0)
        if (fmod((float)$value, (float)$this->value) == 0) {
            return null;
        }

        return $this->message ?? "Property '{$propertyName}' must be divisible by {$this->value}";
    }
}
