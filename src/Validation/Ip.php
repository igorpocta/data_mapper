<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is a valid IP address
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Ip implements AssertInterface
{
    public const V4 = 'v4';
    public const V6 = 'v6';
    public const ALL = 'all';

    public function __construct(
        public readonly string $version = self::ALL,
        public readonly ?string $message = null
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return $this->message ?? "Property '{$propertyName}' must be a string";
        }

        $flags = match($this->version) {
            self::V4 => FILTER_FLAG_IPV4,
            self::V6 => FILTER_FLAG_IPV6,
            default => 0,
        };

        if (filter_var($value, FILTER_VALIDATE_IP, $flags) !== false) {
            return null;
        }

        $versionText = match($this->version) {
            self::V4 => ' (IPv4)',
            self::V6 => ' (IPv6)',
            default => '',
        };

        return $this->message ?? "Property '{$propertyName}' must be a valid IP address{$versionText}";
    }
}
