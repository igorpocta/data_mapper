<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class UrlDecodeFilter implements FilterInterface
{
    public function __construct(
        public readonly bool $raw = true
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        return $this->raw ? rawurldecode($value) : urldecode($value);
    }
}
