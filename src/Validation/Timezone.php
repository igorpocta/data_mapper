<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;
use DateTimeZone;

/**
 * Validates that a value is a valid timezone
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Timezone implements AssertInterface
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

        try {
            new DateTimeZone($value);
            return null;
        } catch (\Exception $e) {
            return $this->message ?? "Property '{$propertyName}' must be a valid timezone";
        }
    }
}
