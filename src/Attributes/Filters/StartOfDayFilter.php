<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;
use DateTimeInterface;
use DateTimeImmutable;
use DateTime;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class StartOfDayFilter implements FilterInterface
{
    public function apply(mixed $value): mixed
    {
        if (!$value instanceof DateTimeInterface) {
            return $value;
        }
        if ($value instanceof DateTimeImmutable) {
            return $value->setTime(0, 0, 0, 0);
        }
        // DateTime instance
        $clone = clone $value;
        $clone->setTime(0, 0, 0, 0);
        return $clone;
    }
}
