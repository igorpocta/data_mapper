<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Formats numbers with locale-specific separators for display.
 * More flexible than MoneyFilter, works with any numeric values.
 *
 * Examples:
 * ```php
 * #[NumberFormatFilter(decimals: 2)]
 * public float $value; // 1234.5678 → "1234.57"
 *
 * #[NumberFormatFilter(decimals: 2, decimalSep: ',', thousandsSep: ' ')]
 * public float $price; // 1234.56 → "1 234,56" (European format)
 *
 * #[NumberFormatFilter(decimals: 0, thousandsSep: ',')]
 * public int $count; // 1234567 → "1,234,567" (US format)
 *
 * #[NumberFormatFilter(decimals: 3, decimalSep: '.', thousandsSep: '')]
 * public float $precise; // 1234.5678 → "1234.568"
 *
 * #[NumberFormatFilter(decimals: 2, prefix: '$', suffix: ' USD')]
 * public float $money; // 1234.56 → "$1234.56 USD"
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class NumberFormatFilter implements FilterInterface
{
    /**
     * @param int $decimals Number of decimal places (default: 0)
     * @param string $decimalSep Decimal separator (default: '.')
     * @param string $thousandsSep Thousands separator (default: '')
     * @param string $prefix Optional prefix to add before number (default: '')
     * @param string $suffix Optional suffix to add after number (default: '')
     */
    public function __construct(
        private int $decimals = 0,
        private string $decimalSep = '.',
        private string $thousandsSep = '',
        private string $prefix = '',
        private string $suffix = ''
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            return $value;
        }

        $formatted = number_format(
            (float) $value,
            $this->decimals,
            $this->decimalSep,
            $this->thousandsSep
        );

        return $this->prefix . $formatted . $this->suffix;
    }
}
