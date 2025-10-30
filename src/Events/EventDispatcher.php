<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Events;

/**
 * Simple event dispatcher for mapper events
 */
class EventDispatcher
{
    /**
     * @var array<string, array<int, array{listener: callable, priority: int}>>
     */
    private array $listeners = [];

    /**
     * Add event listener
     *
     * @param string $eventName Event class name or alias
     * @param callable(EventInterface): void $listener Callable accepting EventInterface
     * @param int $priority Higher priority = called first (default: 0)
     */
    public function addEventListener(string $eventName, callable $listener, int $priority = 0): void
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = [];
        }

        $this->listeners[$eventName][] = [
            'listener' => $listener,
            'priority' => $priority,
        ];

        // Sort by priority (descending)
        usort($this->listeners[$eventName], fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    /**
     * Remove event listener
     *
     * @param string $eventName
     * @param callable(EventInterface): void $listener
     */
    public function removeEventListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        $this->listeners[$eventName] = array_filter(
            $this->listeners[$eventName],
            fn($item) => $item['listener'] !== $listener
        );
    }

    /**
     * Dispatch event to all listeners
     *
     * @param EventInterface $event
     * @return EventInterface The same event (possibly modified by listeners)
     */
    public function dispatch(EventInterface $event): EventInterface
    {
        $eventName = $event->getEventName();

        if (!isset($this->listeners[$eventName])) {
            return $event;
        }

        foreach ($this->listeners[$eventName] as $item) {
            if ($event->isPropagationStopped()) {
                break;
            }

            call_user_func($item['listener'], $event);
        }

        return $event;
    }

    /**
     * Check if event has listeners
     */
    public function hasListeners(string $eventName): bool
    {
        return isset($this->listeners[$eventName]) && !empty($this->listeners[$eventName]);
    }

    /**
     * Get all listeners for an event
     *
     * @return array<int, callable(EventInterface): void>
     */
    public function getListeners(string $eventName): array
    {
        if (!isset($this->listeners[$eventName])) {
            return [];
        }

        return array_map(fn($item) => $item['listener'], $this->listeners[$eventName]);
    }

    /**
     * Remove all listeners for an event or all events
     */
    public function removeAllListeners(?string $eventName = null): void
    {
        if ($eventName === null) {
            $this->listeners = [];
        } else {
            unset($this->listeners[$eventName]);
        }
    }
}
