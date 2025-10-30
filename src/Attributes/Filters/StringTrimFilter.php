<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class StringTrimFilter implements FilterInterface
{
    public function __construct(
        public readonly ?string $characters = null
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            return $value;
        }

        return $this->characters === null
            ? trim($value)
            : trim($value, $this->characters);
    }
}
