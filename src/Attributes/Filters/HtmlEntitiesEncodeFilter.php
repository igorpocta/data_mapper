<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class HtmlEntitiesEncodeFilter implements FilterInterface
{
    public function __construct(
        public readonly int $flags = ENT_QUOTES | ENT_SUBSTITUTE,
        public readonly string $encoding = 'UTF-8',
        public readonly bool $doubleEncode = true
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        return htmlentities($value, $this->flags, $this->encoding, $this->doubleEncode);
    }
}
