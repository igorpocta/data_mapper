<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class EnsurePrefixFilter implements FilterInterface
{
    public function __construct(
        public readonly string $prefix
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }
        return str_starts_with($value, $this->prefix) ? $value : $this->prefix . $value;
    }
}
