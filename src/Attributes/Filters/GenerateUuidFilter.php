<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Generates UUID (Universally Unique Identifier) for null or empty values.
 * Useful for auto-generating unique identifiers.
 *
 * Examples:
 * ```php
 * #[GenerateUuidFilter]
 * public ?string $id; // null → "550e8400-e29b-41d4-a716-446655440000"
 *
 * #[GenerateUuidFilter(version: 4)]
 * public string $uuid; // "" → "6ba7b810-9dad-11d1-80b4-00c04fd430c8"
 *
 * #[GenerateUuidFilter(version: 4, onlyIfNull: true)]
 * public ?string $identifier; // Only generates if null, leaves existing values unchanged
 * ```
 *
 * Note: This filter generates RFC 4122 compliant UUIDs.
 * - Version 4: Random UUID (default and most common)
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class GenerateUuidFilter implements FilterInterface
{
    /**
     * @param int $version UUID version (only version 4 is currently supported) (default: 4)
     * @param bool $onlyIfNull Only generate UUID if value is null, don't replace empty strings (default: false)
     */
    public function __construct(
        private int $version = 4,
        private bool $onlyIfNull = false
    ) {
        if ($this->version !== 4) {
            throw new \InvalidArgumentException('Only UUID version 4 is currently supported');
        }
    }

    public function apply(mixed $value): mixed
    {
        // If value exists and we should only replace null, return as-is
        if ($this->onlyIfNull && $value !== null) {
            return $value;
        }

        // Generate UUID if value is null or empty string
        if ($value === null || $value === '') {
            return $this->generateUuidV4();
        }

        return $value;
    }

    /**
     * Generate a version 4 (random) UUID.
     *
     * @return string RFC 4122 compliant UUID v4
     */
    private function generateUuidV4(): string
    {
        // Generate 16 random bytes
        $data = random_bytes(16);

        // Set version (4) in bits 12-15
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);

        // Set variant (RFC 4122) in bits 6-7
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Format as UUID: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
        return sprintf(
            '%s-%s-%s-%s-%s',
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6))
        );
    }
}
