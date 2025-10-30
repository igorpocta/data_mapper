<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Provides a default value if the input is null or empty
 * Useful for optional fields that should have a fallback value
 *
 * Example:
 * #[DefaultValueFilter('Unknown')]
 * public string $status;
 *
 * Input: null
 * Output: "Unknown"
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class DefaultValueFilter implements FilterInterface
{
    public function __construct(
        public readonly mixed $defaultValue,
        public readonly bool $replaceEmpty = false
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if ($value === null) {
            return $this->defaultValue;
        }

        if ($this->replaceEmpty && $value === '') {
            return $this->defaultValue;
        }

        return $value;
    }
}
