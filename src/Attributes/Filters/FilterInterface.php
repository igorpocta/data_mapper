<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

/**
 * Marker interface for post-normalization filters.
 *
 * Filters implementing this interface can be attached to properties
 * (and optionally parameters) and will be applied by the Normalizer
 * after a property's value is normalized.
 */
interface FilterInterface
{
    /**
     * Applies transformation to the already normalized value.
     * Implementations should be null-safe and conservative: if the value
     * is of an unsupported type, they should return it unchanged.
     *
     * @param mixed $value
     * @return mixed
     */
    public function apply(mixed $value): mixed;
}

