<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class PadRightFilter implements FilterInterface
{
    public function __construct(
        public readonly int $length,
        public readonly string $pad = ' '
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        return str_pad($value, $this->length, $this->pad, STR_PAD_RIGHT);
    }
}
