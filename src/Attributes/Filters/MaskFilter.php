<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Masks sensitive data by replacing characters with a mask string.
 * Useful for GDPR compliance, security, and privacy protection.
 *
 * Examples:
 * ```php
 * #[MaskFilter(mask: '****', visibleStart: 2, visibleEnd: 2)]
 * public string $cardNumber; // "1234567890123456" → "12************56"
 *
 * #[MaskFilter(mask: '***', visibleStart: 3, visibleEnd: 0)]
 * public string $email; // "test@example.com" → "tes***"
 *
 * #[MaskFilter(mask: 'XXXX', visibleStart: 0, visibleEnd: 4)]
 * public string $accountNumber; // "1234567890" → "XXXX7890"
 *
 * #[MaskFilter(mask: '****', visibleStart: 4, visibleEnd: 4, maskChar: '*')]
 * public string $iban; // "CZ6508000000192000145399" → "CZ65****************5399"
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MaskFilter implements FilterInterface
{
    /**
     * @param string $mask Mask string to use for replacement (default: '****')
     * @param int $visibleStart Number of characters visible at start (default: 0)
     * @param int $visibleEnd Number of characters visible at end (default: 0)
     * @param string|null $maskChar If provided, uses this character repeated instead of mask string
     */
    public function __construct(
        private string $mask = '****',
        private int $visibleStart = 0,
        private int $visibleEnd = 0,
        private ?string $maskChar = null
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

        $length = mb_strlen($value);

        // If string is too short to mask, return as-is or fully masked
        if ($length <= ($this->visibleStart + $this->visibleEnd)) {
            return $this->maskChar !== null
                ? str_repeat($this->maskChar, $length)
                : $this->mask;
        }

        $start = mb_substr($value, 0, $this->visibleStart);
        $end = $this->visibleEnd > 0 ? mb_substr($value, -$this->visibleEnd) : '';

        // Calculate masked section length
        $maskedLength = $length - $this->visibleStart - $this->visibleEnd;

        // Use maskChar if provided, otherwise use mask string
        $maskString = $this->maskChar !== null
            ? str_repeat($this->maskChar, $maskedLength)
            : $this->mask;

        return $start . $maskString . $end;
    }
}
