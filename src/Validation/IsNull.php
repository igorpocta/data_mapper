<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is null
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class IsNull implements AssertInterface
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

        return $this->message ?? "Property '{$propertyName}' must be null";
    }
}
