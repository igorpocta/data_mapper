<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class SliceArrayFilter implements FilterInterface
{
    public function __construct(
        public readonly int $offset,
        public readonly ?int $length = null,
        public readonly bool $preserveKeys = true
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        return array_slice($value, $this->offset, $this->length, $this->preserveKeys);
    }
}
