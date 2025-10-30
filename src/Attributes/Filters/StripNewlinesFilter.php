<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class StripNewlinesFilter implements FilterInterface
{
    public function __construct(
        public readonly string $replacement = ''
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

        return str_replace(["\r\n", "\r", "\n"], $this->replacement, $value);
    }
}
