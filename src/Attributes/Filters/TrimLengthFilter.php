<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class TrimLengthFilter implements FilterInterface
{
    public function __construct(
        public readonly int $max,
        public readonly string $ellipsis = '…',
        public readonly ?string $encoding = 'UTF-8'
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $enc = $this->encoding ?? 'UTF-8';
        $len = function_exists('mb_strlen') ? mb_strlen($value, $enc) : strlen($value);
        if ($len <= $this->max) {
            return $value;
        }
        $cutLen = $this->max - ($this->ellipsis !== '' ? (function_exists('mb_strlen') ? mb_strlen($this->ellipsis, $enc) : strlen($this->ellipsis)) : 0);
        if ($cutLen < 0) {
            $cutLen = 0;
        }
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $cutLen, $enc) . $this->ellipsis;
        }
        return substr($value, 0, $cutLen) . $this->ellipsis;
    }
}
