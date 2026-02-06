<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Assert that string matches a regular expression pattern
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Pattern implements AssertInterface
{
    public function __construct(
        public readonly string $pattern,
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
            return "Property '{$propertyName}' must be string for Pattern validation";
        }

        if (!preg_match($this->pattern, $value)) {
            return $this->message ?? "Property '{$propertyName}' must match pattern '{$this->pattern}'";
        }

        return null;
    }
}
