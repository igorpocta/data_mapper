<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Returns the first non-null value from the provided fallback values
 * Similar to PHP's null coalescing operator (??) but for filters
 *
 * Example:
 * #[CoalesceFilter('N/A', 'Unknown')]
 * public ?string $optionalField;
 *
 * If value is null, returns 'N/A'
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class CoalesceFilter implements FilterInterface
{
    /** @var array<mixed> */
    private array $fallbacks;

    public function __construct(mixed ...$fallbacks)
    {
        $this->fallbacks = $fallbacks;
    }

    public function apply(mixed $value): mixed
    {
        if ($value !== null) {
            return $value;
        }

        foreach ($this->fallbacks as $fallback) {
            if ($fallback !== null) {
                return $fallback;
            }
        }

        return null;
    }
}
