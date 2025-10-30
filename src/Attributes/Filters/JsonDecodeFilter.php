<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;
use JsonException;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class JsonDecodeFilter implements FilterInterface
{
    public function __construct(
        public readonly bool $assoc = true
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        try {
            $decoded = json_decode($value, $this->assoc, 512, JSON_THROW_ON_ERROR);
            return $decoded;
        } catch (JsonException) {
            return $value; // leave unchanged on invalid JSON
        }
    }
}
