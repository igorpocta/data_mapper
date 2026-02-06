<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Assert that value is a valid email address
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Email implements AssertInterface
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
            return null; // Use NotNull for null checks
        }

        if (!is_string($value)) {
            return "Property '{$propertyName}' must be string for Email validation";
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->message ?? "Property '{$propertyName}' must be a valid email address";
        }

        return null;
    }
}
