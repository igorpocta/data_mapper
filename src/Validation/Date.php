<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;
use DateTime as PHPDateTime;

/**
 * Validates that a value is a valid date
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Date implements AssertInterface
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

        // Allow DateTime objects
        if ($value instanceof \DateTimeInterface) {
            return null;
        }

        // For strings, try to parse as date
        if (is_string($value)) {
            $date = PHPDateTime::createFromFormat('Y-m-d', $value);
            if ($date !== false && $date->format('Y-m-d') === $value) {
                return null;
            }
        }

        return $this->message ?? "Property '{$propertyName}' must be a valid date (Y-m-d format)";
    }
}
