<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is a valid hostname
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Hostname implements AssertInterface
{
    public function __construct(
        public readonly ?string $message = null,
        /** @var array<string> */
        public readonly array $groups = ['Default'],
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

        // Check hostname validity
        // Hostname regex: alphanumeric and hyphens, max 63 chars per label, max 253 total
        $pattern = '/^(?=.{1,253}$)(?!-)[A-Za-z0-9-]{1,63}(?<!-)(\\.(?!-)[A-Za-z0-9-]{1,63}(?<!-))*$/';

        if (preg_match($pattern, $value) === 1) {
            return null;
        }

        return $this->message ?? "Property '{$propertyName}' must be a valid hostname";
    }
}
