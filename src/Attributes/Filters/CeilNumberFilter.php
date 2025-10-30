<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class CeilNumberFilter implements FilterInterface
{
    public function apply(mixed $value): mixed
    {
        if (!is_numeric($value)) {
            return $value;
        }
        $res = ceil((float) $value);
        return (int) $res;
    }
}
