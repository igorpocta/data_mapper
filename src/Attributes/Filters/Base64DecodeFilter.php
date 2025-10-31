<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Decodes Base64-encoded strings.
 * Useful for decoding encoded data, API tokens, and binary data.
 *
 * Examples:
 * ```php
 * #[Base64DecodeFilter]
 * public string $data; // "aGVsbG8=" → "hello"
 *
 * #[Base64DecodeFilter(urlSafe: true)]
 * public string $urlToken; // Decodes URL-safe Base64 (RFC 4648)
 *
 * #[Base64DecodeFilter(strict: true)]
 * public string $strictData; // Strict mode - throws on invalid encoding
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Base64DecodeFilter implements FilterInterface
{
    /**
     * @param bool $urlSafe Decode URL-safe Base64 (replaces -_ with +/) (default: false)
     * @param bool $strict Use strict mode - returns false on invalid input (default: false)
     */
    public function __construct(
        private bool $urlSafe = false,
        private bool $strict = false
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

        $toDecode = $value;

        if ($this->urlSafe) {
            // Convert URL-safe Base64 back to standard Base64
            $toDecode = strtr($toDecode, '-_', '+/');

            // Add padding if necessary
            $remainder = strlen($toDecode) % 4;
            if ($remainder > 0) {
                $toDecode .= str_repeat('=', 4 - $remainder);
            }
        }

        // First check if value is valid base64
        // Valid base64 only contains A-Za-z0-9+/= characters
        $pattern = $this->urlSafe
            ? '/^[A-Za-z0-9\-_=]*$/'
            : '/^[A-Za-z0-9+\/=]*$/';

        if (!preg_match($pattern, $toDecode)) {
            // Not valid base64 - probably already decoded, return as-is
            return $value;
        }

        $decoded = base64_decode($toDecode, $this->strict);

        // Return original value if decoding failed
        if ($decoded === false) {
            return $value;
        }

        return $decoded;
    }
}
