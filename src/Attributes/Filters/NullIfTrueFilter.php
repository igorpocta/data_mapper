<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class NullIfTrueFilter implements FilterInterface
{
    public function apply(mixed $value): mixed
    {
        if ($value === true) {
            return null;
        }
        return $value;
    }
}
