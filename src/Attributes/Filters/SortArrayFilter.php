<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class SortArrayFilter implements FilterInterface
{
    public function __construct(
        public readonly int $flags = SORT_REGULAR,
        public readonly bool $preserveKeys = false
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        $arr = $value;
        $this->preserveKeys ? asort($arr, $this->flags) : sort($arr, $this->flags);
        return $arr;
    }
}
