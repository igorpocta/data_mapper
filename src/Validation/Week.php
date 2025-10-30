<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is a valid ISO 8601 week (e.g., 2024-W01)
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Week implements AssertInterface
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

        if (!is_string($value)) {
            return $this->message ?? "Property '{$propertyName}' must be a string";
        }

        // ISO 8601 week format: YYYY-Www (e.g., 2024-W01)
        if (preg_match('/^\d{4}-W(0[1-9]|[1-4][0-9]|5[0-3])$/', $value) === 1) {
            return null;
        }

        return $this->message ?? "Property '{$propertyName}' must be a valid ISO 8601 week (e.g., 2024-W01)";
    }
}
