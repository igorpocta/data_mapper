<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ToStringFilter implements FilterInterface
{
    public function __construct(
        public readonly bool $trim = false
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            $str = (string) $value;
            return $this->trim ? trim($str) : $str;
        }

        return $value; // keep arrays/objects as-is to avoid surprising behavior
    }
}

