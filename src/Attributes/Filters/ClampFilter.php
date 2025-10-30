<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ClampFilter implements FilterInterface
{
    public function __construct(
        public readonly float|int $min = PHP_FLOAT_MIN,
        public readonly float|int $max = PHP_FLOAT_MAX
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_numeric($value)) {
            return $value;
        }
        $num = (float) $value;
        if ($num < $this->min) {
            return $this->min;
        }
        if ($num > $this->max) {
            return $this->max;
        }
        // Preserve int if possible
        return floor($num) == $num ? (int) $num : $num;
    }
}
