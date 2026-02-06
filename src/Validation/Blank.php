<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is blank (empty string or null)
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Blank implements AssertInterface
{
    public function __construct(
        public readonly ?string $message = null,
        /** @var array<string> */
        public readonly array $groups = ['Default'],
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        // Allow null
        if ($value === null) {
            return null;
        }

        // Check if it's a blank string
        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return $this->message ?? "Property '{$propertyName}' must be blank";
    }
}
