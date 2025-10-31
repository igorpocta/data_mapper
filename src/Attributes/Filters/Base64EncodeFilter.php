<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Encodes strings to Base64 format.
 * Useful for encoding binary data, API tokens, and data transmission.
 *
 * Examples:
 * ```php
 * #[Base64EncodeFilter]
 * public string $token; // "hello" → "aGVsbG8="
 *
 * #[Base64EncodeFilter(urlSafe: true)]
 * public string $urlToken; // Uses URL-safe Base64 encoding (RFC 4648)
 *
 * #[Base64EncodeFilter(removePadding: true)]
 * public string $compact; // "hello" → "aGVsbG8" (without padding)
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Base64EncodeFilter implements FilterInterface
{
    /**
     * @param bool $urlSafe Use URL-safe Base64 encoding (replaces +/ with -_) (default: false)
     * @param bool $removePadding Remove padding characters (=) from output (default: false)
     */
    public function __construct(
        private bool $urlSafe = false,
        private bool $removePadding = false
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

        $encoded = base64_encode($value);

        if ($this->urlSafe) {
            // Replace + with - and / with _ for URL-safe encoding
            $encoded = strtr($encoded, '+/', '-_');
        }

        if ($this->removePadding) {
            // Remove padding characters
            $encoded = rtrim($encoded, '=');
        }

        return $encoded;
    }
}
