<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is identical to another value (strict ===)
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class IdenticalTo implements AssertInterface
{
    public function __construct(
        public readonly mixed $value,
        public readonly ?string $message = null
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        // Use === for strict comparison (including null)
        if ($value === $this->value) {
            return null;
        }

        $expectedType = get_debug_type($this->value);
        $actualType = get_debug_type($value);
        $valueStr = is_scalar($this->value) ? (string)$this->value : $expectedType;

        return $this->message ?? "Property '{$propertyName}' must be identical to '{$valueStr}' (expected {$expectedType}, got {$actualType})";
    }
}
