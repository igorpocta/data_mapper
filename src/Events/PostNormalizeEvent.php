<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Events;

/**
 * Event dispatched after successful normalization
 * Allows modification of the resulting array
 */
class PostNormalizeEvent extends AbstractEvent
{
    /**
     * @param array<string, mixed> $data Normalized data
     * @param object $originalObject Original object
     */
    public function __construct(
        public array $data,
        public readonly object $originalObject
    ) {
    }

    /**
     * Modify output data
     *
     * @param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Get original object class name
     */
    public function getClassName(): string
    {
        return get_class($this->originalObject);
    }
}
