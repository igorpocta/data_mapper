<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is equal to another value
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class EqualTo implements AssertInterface
{
    public function __construct(
        public readonly mixed $value,
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

        // Use == for loose comparison
        if ($value == $this->value) {
            return null;
        }

        $valueStr = is_scalar($this->value) ? (string)$this->value : get_debug_type($this->value);
        return $this->message ?? "Property '{$propertyName}' must be equal to '{$valueStr}'";
    }
}
