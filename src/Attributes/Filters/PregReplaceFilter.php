<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class PregReplaceFilter implements FilterInterface
{
    public function __construct(
        public readonly string $pattern,
        public readonly string $replacement,
        public readonly int $limit = -1
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

        $result = @preg_replace($this->pattern, $this->replacement, $value, $this->limit);
        return $result === null ? $value : $result;
    }
}
