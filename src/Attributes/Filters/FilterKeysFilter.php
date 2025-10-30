<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class FilterKeysFilter implements FilterInterface
{
    /**
     * @param array<int, int|string> $allow
     * @param array<int, int|string> $deny
     */
    public function __construct(
        public readonly array $allow = [],
        public readonly array $deny = []
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        $arr = $value;
        if ($this->allow !== []) {
            $allowKeys = array_flip($this->allow);
            $arr = array_intersect_key($arr, $allowKeys);
        }
        if ($this->deny !== []) {
            foreach ($this->deny as $k) {
                unset($arr[$k]);
            }
        }
        return $arr;
    }
}
