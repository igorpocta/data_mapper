<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class SlugifyFilter implements FilterInterface
{
    public function __construct(
        public readonly string $separator = '-',
        public readonly bool $lower = true
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Transliterate accents
        $filtered = (new ReplaceDiacriticsFilter())->apply($value);
        if (!is_string($filtered)) {
            return $value;
        }
        // Replace non alnum with separator
        $filtered = preg_replace('~[^A-Za-z0-9]+~', $this->separator, $filtered);
        if (!is_string($filtered)) {
            return $value;
        }
        // Trim separators
        $filtered = trim($filtered, $this->separator);
        // Collapse repeated separators
        $filtered = preg_replace('~' . preg_quote($this->separator, '~') . '+~', $this->separator, $filtered);
        if (!is_string($filtered)) {
            return $value;
        }
        if ($this->lower) {
            $filtered = function_exists('mb_strtolower') ? mb_strtolower($filtered, 'UTF-8') : strtolower($filtered);
        }
        return $filtered;
    }
}
