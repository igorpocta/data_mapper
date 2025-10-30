<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class NullIfFalseFilter implements FilterInterface
{
    public function apply(mixed $value): mixed
    {
        if ($value === false) {
            return null;
        }
        return $value;
    }
}
