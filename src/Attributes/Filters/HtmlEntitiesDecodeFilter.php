<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class HtmlEntitiesDecodeFilter implements FilterInterface
{
    public function __construct(
        public readonly int $flags = ENT_QUOTES,
        public readonly string $encoding = 'UTF-8'
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        return html_entity_decode($value, $this->flags, $this->encoding);
    }
}
