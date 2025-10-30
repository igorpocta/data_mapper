<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ToDecimalStringFilter implements FilterInterface
{
    public function __construct(
        public readonly ?int $precision = null
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (is_string($value)) {
            // Normalize spaces and commas
            $v = str_replace([' ', "\u{00A0}"], '', $value);
            $v = str_replace(',', '.', $v);
            if (is_numeric($v)) {
                $value = $v;
            } else {
                return $value;
            }
        }

        if (!is_numeric($value)) {
            return $value;
        }

        $num = (string) (float) $value;
        if ($this->precision !== null) {
            return number_format((float) $value, $this->precision, '.', '');
        }
        // Default: remove scientific notation, ensure dot decimal
        if (!str_contains($num, 'E') && !str_contains($num, 'e')) {
            return $num;
        }
        return sprintf('%.14F', (float) $value);
    }
}
