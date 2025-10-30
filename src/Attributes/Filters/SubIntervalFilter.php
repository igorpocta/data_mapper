<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;
use DateInterval;
use DateTimeInterface;
use DateTimeImmutable;
use DateTime;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class SubIntervalFilter implements FilterInterface
{
    public function __construct(
        public readonly string $intervalSpec
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!$value instanceof DateTimeInterface) {
            return $value;
        }
        try {
            $interval = new DateInterval($this->intervalSpec);
        } catch (\Throwable) {
            return $value;
        }
        if ($value instanceof DateTimeImmutable) {
            return $value->sub($interval);
        }
        // DateTime instance
        $clone = clone $value;
        $clone->sub($interval);
        return $clone;
    }
}
