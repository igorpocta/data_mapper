<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates the number of elements in a countable value (array, Countable)
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Count implements AssertInterface
{
    /**
     * @param int|null $min Minimum number of elements
     * @param int|null $max Maximum number of elements
     * @param int|null $exactly Exact number of elements required
     * @param string|null $message Custom error message
     * @param array<string> $groups Validation groups
     */
    public function __construct(
        public readonly ?int $min = null,
        public readonly ?int $max = null,
        public readonly ?int $exactly = null,
        public readonly ?string $message = null,
        public readonly array $groups = ['Default'],
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_countable($value)) {
            return "Property '{$propertyName}' must be countable";
        }

        $count = count($value);

        if ($this->exactly !== null && $count !== $this->exactly) {
            return $this->message ?? "Property '{$propertyName}' must contain exactly {$this->exactly} elements";
        }

        if ($this->min !== null && $count < $this->min) {
            return $this->message ?? "Property '{$propertyName}' must contain at least {$this->min} elements";
        }

        if ($this->max !== null && $count > $this->max) {
            return $this->message ?? "Property '{$propertyName}' must contain at most {$this->max} elements";
        }

        return null;
    }
}
