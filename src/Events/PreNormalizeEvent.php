<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Events;

/**
 * Event dispatched before normalization (object → array/JSON)
 * Allows modification of object before conversion
 */
class PreNormalizeEvent extends AbstractEvent
{
    /**
     * @param object $object Object to be normalized
     */
    public function __construct(
        public object $object
    ) {
    }

    /**
     * Replace the object with a different instance
     */
    public function setObject(object $object): void
    {
        $this->object = $object;
    }

    /**
     * Get object class name
     */
    public function getClassName(): string
    {
        return get_class($this->object);
    }
}
