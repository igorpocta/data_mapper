<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class LimitArrayFilter implements FilterInterface
{
    public function __construct(
        public readonly int $max,
        public readonly bool $preserveKeys = true
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        if ($this->max < 0) {
            return $value;
        }
        return array_slice($value, 0, $this->max, $this->preserveKeys);
    }
}
