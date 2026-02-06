<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is a valid datetime
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class DateTime implements AssertInterface
{
    public function __construct(
        public readonly ?string $format = null,
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

        // For strings, try to parse as datetime
        if (is_string($value)) {
            if ($this->format !== null) {
                // Validate with specific format
                $date = \DateTime::createFromFormat($this->format, $value);
                if ($date !== false && $date->format($this->format) === $value) {
                    return null;
                }
            } else {
                // Try to parse with strtotime
                if (strtotime($value) !== false) {
                    return null;
                }
            }
        }

        $formatMsg = $this->format ? " (format: {$this->format})" : "";
        return $this->message ?? "Property '{$propertyName}' must be a valid datetime{$formatMsg}";
    }
}
