<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Pocta\DataMapper\Attributes\DiscriminatorMap;
use Pocta\DataMapper\Attributes\MapProperty;

/**
 * Base event class for demonstrating nested discriminator mapping
 */
#[DiscriminatorMap(
    property: 'event_type',
    mapping: [
        'user_created' => UserCreatedEvent::class,
        'order_placed' => OrderPlacedEvent::class,
    ]
)]
abstract class Event
{
    #[MapProperty(name: 'event_type')]
    protected string $eventType;

    protected string $timestamp;

    public function __construct(string $eventType, string $timestamp)
    {
        $this->eventType = $eventType;
        $this->timestamp = $timestamp;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getTimestamp(): string
    {
        return $this->timestamp;
    }
}
