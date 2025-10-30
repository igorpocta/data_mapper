<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class StripTagsFilter implements FilterInterface
{
    public function __construct(
        public readonly ?string $allowedTags = null
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            return $value;
        }

        return $this->allowedTags === null
            ? strip_tags($value)
            : strip_tags($value, $this->allowedTags);
    }
}
