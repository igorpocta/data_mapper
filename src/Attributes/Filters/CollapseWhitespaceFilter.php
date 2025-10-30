<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class CollapseWhitespaceFilter implements FilterInterface
{
    public function __construct(
        public readonly bool $trim = true
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $collapsed = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return $this->trim ? trim($collapsed) : $collapsed;
    }
}
