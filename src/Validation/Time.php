<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;
use DateTime as PHPDateTime;

/**
 * Validates that a value is a valid time
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Time implements AssertInterface
{
    public function __construct(
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

        // Allow DateTime objects
        if ($value instanceof \DateTimeInterface) {
            return null;
        }

        // For strings, try to parse as time
        if (is_string($value)) {
            $time = PHPDateTime::createFromFormat('H:i:s', $value);
            if ($time !== false && $time->format('H:i:s') === $value) {
                return null;
            }

            // Also accept H:i format
            $time = PHPDateTime::createFromFormat('H:i', $value);
            if ($time !== false && $time->format('H:i') === $value) {
                return null;
            }
        }

        return $this->message ?? "Property '{$propertyName}' must be a valid time (H:i:s or H:i format)";
    }
}
