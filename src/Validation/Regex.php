<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that the value matches a regular expression pattern
 * More flexible alternative to Pattern validator with better naming
 *
 * Example:
 * #[Regex('/^[A-Z]{3}$/')]
 * public string $code; // Must be 3 uppercase letters
 *
 * #[Regex('/^\d{6}$/', message: 'Postal code must be 6 digits')]
 * public string $postalCode;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Regex implements AssertInterface
{
    public function __construct(
        public readonly string $pattern,
        public readonly string $message = 'Value does not match the required pattern'
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return "Property '{$propertyName}' must be string for validation";
        }

        if (preg_match($this->pattern, $value) !== 1) {
            return str_replace('Value', "Property '{$propertyName}'", $this->message);
        }

        return null;
    }

    
}
