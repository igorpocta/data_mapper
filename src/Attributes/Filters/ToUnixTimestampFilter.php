<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;
use DateTimeInterface;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ToUnixTimestampFilter implements FilterInterface
{
    public function apply(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }
        if (is_string($value)) {
            $ts = strtotime($value);
            if ($ts !== false) {
                return $ts;
            }
        }
        return $value;
    }
}
