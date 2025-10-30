<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ToBoolStrictFilter implements FilterInterface
{
    /**
     * @param array<int, int|string|bool|null> $trueValues
     * @param array<int, int|string|bool|null> $falseValues
     */
    public function __construct(
        public readonly array $trueValues = [true, 'true', '1', 1, 'yes', 'y', 'on'],
        public readonly array $falseValues = [false, 'false', '0', 0, 'no', 'n', 'off', '']
    ) {
    }

    public function apply(mixed $value): mixed
    {
        // Already boolean
        if (is_bool($value)) {
            return $value;
        }
        // Normalize string
        if (is_string($value)) {
            $v = strtolower(trim($value));
            if (in_array($v, array_map(fn($x) => is_string($x) ? strtolower((string)$x) : $x, $this->trueValues), true)) {
                return true;
            }
            if (in_array($v, array_map(fn($x) => is_string($x) ? strtolower((string)$x) : $x, $this->falseValues), true)) {
                return false;
            }
            return $value;
        }

        // Numbers/null
        if (in_array($value, $this->trueValues, true)) {
            return true;
        }
        if (in_array($value, $this->falseValues, true)) {
            return false;
        }
        return $value;
    }
}
