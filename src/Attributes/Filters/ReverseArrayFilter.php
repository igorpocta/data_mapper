<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ReverseArrayFilter implements FilterInterface
{
    public function __construct(
        public readonly bool $preserveKeys = true
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        return array_reverse($value, $this->preserveKeys);
    }
}
