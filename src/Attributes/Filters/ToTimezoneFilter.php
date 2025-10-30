<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;
use DateTimeInterface;
use DateTimeZone;
use DateTimeImmutable;
use DateTime;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ToTimezoneFilter implements FilterInterface
{
    public function __construct(
        public readonly string $timezone
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!$value instanceof DateTimeInterface) {
            return $value;
        }
        try {
            $tz = new DateTimeZone($this->timezone);
        } catch (\Throwable) {
            return $value;
        }
        if ($value instanceof DateTimeImmutable) {
            return $value->setTimezone($tz);
        }
        // DateTime instance
        $clone = clone $value;
        $clone->setTimezone($tz);
        return $clone;
    }
}
