<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Normalizes email address to lowercase and trims whitespace
 * Useful for user registration/login where emails should be case-insensitive
 *
 * Example:
 * #[NormalizeEmailFilter]
 * public string $email;
 *
 * Input: "  John.Doe@EXAMPLE.COM  "
 * Output: "john.doe@example.com"
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class NormalizeEmailFilter implements FilterInterface
{
    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return strtolower(trim($value));
    }
}
