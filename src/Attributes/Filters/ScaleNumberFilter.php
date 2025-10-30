<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ScaleNumberFilter implements FilterInterface
{
    public function __construct(
        public readonly ?float $multiplyBy = null,
        public readonly ?float $divideBy = null
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_numeric($value)) {
            return $value;
        }
        $num = (float) $value;
        if ($this->multiplyBy !== null) {
            $num *= $this->multiplyBy;
        }
        if ($this->divideBy !== null && $this->divideBy != 0.0) {
            $num /= $this->divideBy;
        }
        return floor($num) == $num ? (int) $num : $num;
    }
}
