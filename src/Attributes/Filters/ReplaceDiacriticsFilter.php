<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ReplaceDiacriticsFilter implements FilterInterface
{
    public function apply(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        // Try intl Transliterator if available
        if (class_exists('Transliterator')) {
            $trans = \Transliterator::create('Any-Latin; Latin-ASCII; [:Nonspacing Mark:] Remove; NFC');
            if ($trans) {
                $res = $trans->transliterate($value);
                if ($res !== false) {
                    return $res;
                }
            }
        }

        // Fallback to iconv transliteration
        $res = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return $res !== false ? $res : $value;
    }
}
