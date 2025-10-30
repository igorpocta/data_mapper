<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class CapitalizeFirstFilter implements FilterInterface
{
    public function __construct(
        public readonly ?string $encoding = 'UTF-8',
        public readonly bool $lowerRest = false
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }
        $enc = $this->encoding ?? 'UTF-8';
        if (function_exists('mb_strtoupper') && function_exists('mb_substr') && function_exists('mb_strlen')) {
            $first = mb_substr($value, 0, 1, $enc);
            $rest = mb_substr($value, 1, null, $enc);
            $rest = $this->lowerRest && function_exists('mb_strtolower') ? mb_strtolower($rest, $enc) : $rest;
            return mb_strtoupper($first, $enc) . $rest;
        }
        return strtoupper($value[0]) . ($this->lowerRest ? strtolower(substr($value, 1)) : substr($value, 1));
    }
}
