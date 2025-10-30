<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Events;

/**
 * Event dispatched after successful denormalization
 * Allows modification of the created object
 */
class PostDenormalizeEvent extends AbstractEvent
{
    /**
     * @param object $object Created object
     * @param array<string, mixed> $originalData Original input data
     * @param class-string $className Class name of the object
     */
    public function __construct(
        public object $object,
        public readonly array $originalData,
        public readonly string $className
    ) {
    }

    /**
     * Replace the object with a different instance
     */
    public function setObject(object $object): void
    {
        $this->object = $object;
    }
}
