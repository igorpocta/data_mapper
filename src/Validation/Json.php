<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is valid JSON
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Json implements AssertInterface
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

        if (!is_string($value)) {
            return $this->message ?? "Property '{$propertyName}' must be a string to validate as JSON";
        }

        json_decode($value);

        if (json_last_error() === JSON_ERROR_NONE) {
            return null;
        }

        return $this->message ?? "Property '{$propertyName}' must be valid JSON";
    }
}
