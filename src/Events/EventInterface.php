<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Events;

/**
 * Base interface for all events
 */
interface EventInterface
{
    /**
     * Check if event propagation is stopped
     */
    public function isPropagationStopped(): bool;

    /**
     * Stop event propagation
     */
    public function stopPropagation(): void;

    /**
     * Get event name
     */
    public function getEventName(): string;
}
