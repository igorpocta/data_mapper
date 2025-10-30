<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ToNullFilter implements FilterInterface
{
    /**
     * @param array<int, string|int|float|bool|null> $values Values that should be converted to null
     */
    public function __construct(
        public readonly array $values = ['']
    ) {
    }

    public function apply(mixed $value): mixed
    {
        // Always leave null as null
        if ($value === null) {
            return null;
        }

        // Strict comparison against configured values; for strings also compare trimmed version to catch whitespace-only
        foreach ($this->values as $needle) {
            if (is_string($needle) && is_string($value)) {
                if (trim($value) === $needle) {
                    return null;
                }
            }

            if ($value === $needle) {
                return null;
            }
        }

        return $value;
    }
}
