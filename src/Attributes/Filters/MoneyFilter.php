<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Formats numeric values as money strings with configurable separators.
 *
 * Examples:
 * ```php
 * #[MoneyFilter(decimals: 2)]
 * public float $price; // 1234.56 → "1234.56"
 *
 * #[MoneyFilter(decimals: 2, decimalSeparator: ',', thousandsSeparator: ' ')]
 * public float $amount; // 1234.56 → "1 234,56"
 *
 * #[MoneyFilter(decimals: 0, thousandsSeparator: ',')]
 * public int $total; // 1234 → "1,234"
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MoneyFilter implements FilterInterface
{
    /**
     * @param int $decimals Number of decimal places (default: 2)
     * @param string $decimalSeparator Decimal separator (default: '.')
     * @param string $thousandsSeparator Thousands separator (default: '')
     */
    public function __construct(
        private int $decimals = 2,
        private string $decimalSeparator = '.',
        private string $thousandsSeparator = ''
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

        return number_format(
            (float) $value,
            $this->decimals,
            $this->decimalSeparator,
            $this->thousandsSeparator
        );
    }
}
