<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;
use DateTimeInterface;
use DateTimeImmutable;
use DateTime;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class EndOfDayFilter implements FilterInterface
{
    public function apply(mixed $value): mixed
    {
        if (!$value instanceof DateTimeInterface) {
            return $value;
        }
        if ($value instanceof DateTimeImmutable) {
            return $value->setTime(23, 59, 59, 999999);
        }
        // DateTime instance
        $clone = clone $value;
        $clone->setTime(23, 59, 59, 999999);
        return $clone;
    }
}
