<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Assert that value is a valid URL
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Url implements AssertInterface
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
            return "Property '{$propertyName}' must be string for Url validation";
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return $this->message ?? "Property '{$propertyName}' must be a valid URL";
        }

        return null;
    }
}
