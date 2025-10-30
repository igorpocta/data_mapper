<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class TitleCaseFilter implements FilterInterface
{
    public function __construct(
        public readonly ?string $encoding = 'UTF-8',
        public readonly bool $lowerRest = true
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $enc = $this->encoding ?? 'UTF-8';
        $str = $this->lowerRest && function_exists('mb_strtolower')
            ? mb_strtolower($value, $enc)
            : $value;
        if (function_exists('mb_convert_case')) {
            return mb_convert_case($str, MB_CASE_TITLE, $enc);
        }
        // Fallback: naive title case
        return ucwords($str);
    }
}
