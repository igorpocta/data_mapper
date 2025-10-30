<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Events;

/**
 * Abstract base event with propagation control
 */
abstract class AbstractEvent implements EventInterface
{
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function getEventName(): string
    {
        return static::class;
    }
}
