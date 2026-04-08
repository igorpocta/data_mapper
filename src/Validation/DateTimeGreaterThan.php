<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Validates that a datetime value is strictly after a given datetime
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class DateTimeGreaterThan implements AssertInterface
{
    public readonly DateTimeImmutable $threshold;

    public function __construct(
        string $value,
        public readonly ?string $message = null,
        /** @var array<string> */
        public readonly array $groups = ['Default'],
    ) {
        $parsed = new DateTimeImmutable($value);
        $this->threshold = $parsed;
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof DateTimeInterface) {
            return $this->message ?? "Property '{$propertyName}' must be a DateTimeInterface instance";
        }

        if ($value > $this->threshold) {
            return null;
        }

        return $this->message ?? "Property '{$propertyName}' must be after {$this->threshold->format('Y-m-d H:i:s')}";
    }
}
