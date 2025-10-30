<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Assert that string length is within range
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Length implements AssertInterface
{
    public function __construct(
        public readonly ?int $min = null,
        public readonly ?int $max = null,
        public readonly ?int $exact = null,
        public readonly ?string $message = null
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        if ($value === null) {
            return null; // Use NotNull for null checks
        }

        if (!is_string($value)) {
            return "Property '{$propertyName}' must be string for Length validation";
        }

        $length = mb_strlen($value);

        if ($this->exact !== null && $length !== $this->exact) {
            return $this->message ?? "Property '{$propertyName}' must be exactly {$this->exact} characters long";
        }

        if ($this->min !== null && $length < $this->min) {
            return $this->message ?? "Property '{$propertyName}' must be at least {$this->min} characters long";
        }

        if ($this->max !== null && $length > $this->max) {
            return $this->message ?? "Property '{$propertyName}' must be at most {$this->max} characters long";
        }

        return null;
    }
}
