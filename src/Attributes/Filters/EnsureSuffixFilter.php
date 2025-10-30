<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class EnsureSuffixFilter implements FilterInterface
{
    public function __construct(
        public readonly string $suffix
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }
        return str_ends_with($value, $this->suffix) ? $value : $value . $this->suffix;
    }
}
