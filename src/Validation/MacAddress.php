<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that the value is a valid MAC address
 * Supports various formats: 00:1A:2B:3C:4D:5E, 00-1A-2B-3C-4D-5E, 001A.2B3C.4D5E
 *
 * Example:
 * #[MacAddress]
 * public string $macAddress;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MacAddress implements AssertInterface
{
    public function __construct(
        public readonly string $message = 'Value must be a valid MAC address',
        /** @var array<string> */
        public readonly array $groups = ['Default'],
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

        // Colon format: 00:1A:2B:3C:4D:5E
        if (preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value)) {
            return null;
        }

        // Dot format: 001A.2B3C.4D5E
        if (preg_match('/^([0-9A-Fa-f]{4}\.){2}([0-9A-Fa-f]{4})$/', $value)) {
            return null;
        }

        // No separator: 001A2B3C4D5E
        if (preg_match('/^[0-9A-Fa-f]{12}$/', $value)) {
            return null;
        }

        return str_replace('Value', "Property '{$propertyName}'", $this->message);
    }

    
}
