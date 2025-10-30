<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is not blank (not empty string)
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class NotBlank implements AssertInterface
{
    public function __construct(
        public readonly ?string $message = null
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        // Fail on null
        if ($value === null) {
            return $this->message ?? "Property '{$propertyName}' must not be blank";
        }

        // Check if it's a non-blank string
        if (is_string($value) && trim($value) !== '') {
            return null;
        }

        // For non-strings, check if it's not empty
        if (!is_string($value) && !empty($value)) {
            return null;
        }

        return $this->message ?? "Property '{$propertyName}' must not be blank";
    }
}
