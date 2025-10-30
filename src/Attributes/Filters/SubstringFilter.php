<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class SubstringFilter implements FilterInterface
{
    public function __construct(
        public readonly int $start,
        public readonly ?int $length = null,
        public readonly ?string $encoding = 'UTF-8'
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $enc = $this->encoding ?? 'UTF-8';
        if (function_exists('mb_substr')) {
            return mb_substr($value, $this->start, $this->length, $enc);
        }
        return substr($value, $this->start, $this->length ?? strlen($value));
    }
}
