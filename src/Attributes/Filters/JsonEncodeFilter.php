<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;
use JsonException;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class JsonEncodeFilter implements FilterInterface
{
    public function __construct(
        public readonly int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_array($value) && !is_object($value)) {
            return $value;
        }
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | $this->flags);
        } catch (JsonException) {
            return $value;
        }
    }
}
