<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class FlattenArrayFilter implements FilterInterface
{
    public function __construct(
        public readonly int $depth = -1,
        public readonly bool $preserveKeys = false
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        return $this->flatten($value, $this->depth);
    }

    /**
     * @param array<mixed> $arr
     * @return array<mixed>
     */
    private function flatten(array $arr, int $depth): array
    {
        $result = [];
        foreach ($arr as $k => $v) {
            if (is_array($v) && ($depth !== 0)) {
                $items = $this->flatten($v, $depth > 0 ? $depth - 1 : -1);
                foreach ($items as $ik => $iv) {
                    $result[] = $iv;
                }
            } else {
                $result[] = $v;
            }
        }
        return $result;
    }
}
