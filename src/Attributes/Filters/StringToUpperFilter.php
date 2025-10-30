<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class StringToUpperFilter implements FilterInterface
{
    public function __construct(
        public readonly ?string $encoding = null
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

        $enc = $this->encoding ?? 'UTF-8';
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($value, $enc);
        }

        return strtoupper($value);
    }
}
