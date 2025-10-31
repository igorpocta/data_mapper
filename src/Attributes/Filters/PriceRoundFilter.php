<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Rounds prices to psychological pricing points (e.g., $9.99, $99, $199).
 * Common in e-commerce and retail for attractive pricing.
 *
 * Examples:
 * ```php
 * #[PriceRoundFilter(to: 9)]
 * public float $price; // 123.45 → 129.00
 *
 * #[PriceRoundFilter(to: 99)]
 * public float $price; // 123.45 → 199.00
 *
 * #[PriceRoundFilter(to: 95)]
 * public float $price; // 123.45 → 195.00
 *
 * #[PriceRoundFilter(to: 0)]
 * public float $price; // 123.45 → 120.00 (round to nearest 10)
 *
 * #[PriceRoundFilter(to: 99, subtract: true)]
 * public float $price; // 123.45 → 99.00 (round down to 99)
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class PriceRoundFilter implements FilterInterface
{
    /**
     * @param int $to Target ending (0-99): 9 for x.99, 95 for x.95, 0 for round numbers
     * @param bool $subtract If true, rounds down to target instead of up (default: false)
     */
    public function __construct(
        private int $to = 99,
        private bool $subtract = false
    ) {
        if ($this->to < 0 || $this->to > 99) {
            throw new \InvalidArgumentException('Target must be between 0 and 99');
        }
    }

    public function apply(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            return $value;
        }

        $value = (float) $value;

        // Handle special case: round to nearest 10
        if ($this->to === 0) {
            return $this->subtract
                ? floor($value / 10) * 10
                : ceil($value / 10) * 10;
        }

        // Calculate the base (e.g., 100 for .99 ending, 10 for .9 ending)
        $base = $this->to >= 10 ? 100 : 10;

        // Check if already at target ending (idempotent)
        $remainder = fmod($value, $base);
        if (abs($remainder - $this->to) < 0.01) {
            // Already at target ending
            return $value;
        }

        // Calculate the next target value
        $currentBase = floor($value / $base) * $base;
        $targetValue = $currentBase + $this->to;

        if ($this->subtract) {
            // Round down to target - if current target is higher than value, use previous one
            if ($targetValue > $value) {
                return $targetValue - $base;
            }
            return $targetValue;
        } else {
            // Round up to target - if current target is lower or equal to value, use next one
            if ($targetValue <= $value) {
                return $targetValue + $base;
            }
            return $targetValue;
        }
    }
}
