<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Normalizes phone number by removing all non-digit characters
 * Useful for storing phone numbers in a consistent format
 *
 * Example:
 * #[NormalizePhoneFilter]
 * public string $phone;
 *
 * Input: "+1 (555) 123-4567"
 * Output: "15551234567"
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class NormalizePhoneFilter implements FilterInterface
{
    public function __construct(
        public readonly bool $keepPlus = false
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if ($this->keepPlus && str_starts_with(trim($value), '+')) {
            return '+' . preg_replace('/[^0-9]/', '', $value);
        }

        return preg_replace('/[^0-9]/', '', $value);
    }
}
