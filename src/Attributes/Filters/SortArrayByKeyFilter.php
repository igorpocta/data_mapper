<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class SortArrayByKeyFilter implements FilterInterface
{
    public function __construct(
        public readonly int $flags = SORT_REGULAR
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        $arr = $value;
        ksort($arr, $this->flags);
        return $arr;
    }
}
