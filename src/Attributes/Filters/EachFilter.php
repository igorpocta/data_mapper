<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;
use InvalidArgumentException;
use ReflectionClass;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class EachFilter implements FilterInterface
{
    /**
     * @param class-string<FilterInterface> $filterClass
     * @param array<int, mixed> $args Constructor args for the nested filter
     */
    public function __construct(
        public readonly string $filterClass,
        public readonly array $args = []
    ) {
        // @phpstan-ignore-next-line - Type is guaranteed by class-string<FilterInterface>
        if (!is_subclass_of($this->filterClass, FilterInterface::class, true)) {
            throw new InvalidArgumentException("Filter class '{$this->filterClass}' must implement FilterInterface");
        }
    }

    public function apply(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        $ref = new ReflectionClass($this->filterClass);
        /** @var FilterInterface $filter */
        $filter = $ref->newInstanceArgs($this->args);

        $out = [];
        foreach ($value as $k => $v) {
            $out[$k] = $filter->apply($v);
        }
        return $out;
    }
}
