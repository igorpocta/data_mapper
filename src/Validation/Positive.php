<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Assert that numeric value is positive (> 0)
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Positive implements AssertInterface
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

        if (!is_numeric($value)) {
            return "Property '{$propertyName}' must be numeric for Positive validation";
        }

        $numValue = is_int($value) || is_float($value) ? $value : (float) $value;

        if ($numValue <= 0) {
            return $this->message ?? "Property '{$propertyName}' must be positive (greater than 0)";
        }

        return null;
    }
}
