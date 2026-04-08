<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Validates that a datetime value is within a given range (inclusive)
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class DateTimeRange implements AssertInterface
{
    public readonly ?DateTimeImmutable $min;
    public readonly ?DateTimeImmutable $max;

    public function __construct(
        ?string $min = null,
        ?string $max = null,
        public readonly ?string $message = null,
        /** @var array<string> */
        public readonly array $groups = ['Default'],
    ) {
        $this->min = $min !== null ? new DateTimeImmutable($min) : null;
        $this->max = $max !== null ? new DateTimeImmutable($max) : null;
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof DateTimeInterface) {
            return $this->message ?? "Property '{$propertyName}' must be a DateTimeInterface instance";
        }

        if ($this->min !== null && $value < $this->min) {
            return $this->message ?? "Property '{$propertyName}' must be at or after {$this->min->format('Y-m-d H:i:s')}";
        }

        if ($this->max !== null && $value > $this->max) {
            return $this->message ?? "Property '{$propertyName}' must be at or before {$this->max->format('Y-m-d H:i:s')}";
        }

        return null;
    }
}
