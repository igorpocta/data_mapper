<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;
use DateTimeInterface;
use DateTimeImmutable;
use DateTime;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class EnsureImmutableFilter implements FilterInterface
{
    public function apply(mixed $value): mixed
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }
        if ($value instanceof DateTime) {
            return DateTimeImmutable::createFromMutable($value);
        }
        return $value;
    }
}
