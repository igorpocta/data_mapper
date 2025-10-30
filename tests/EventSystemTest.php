<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Events\EventDispatcher;
use Pocta\DataMapper\Events\PreDenormalizeEvent;
use Pocta\DataMapper\Events\PostDenormalizeEvent;
use Pocta\DataMapper\Events\PreNormalizeEvent;
use Pocta\DataMapper\Events\PostNormalizeEvent;
use Pocta\DataMapper\Events\DenormalizationErrorEvent;

class EventSystemTest extends TestCase
{
    public function testEventDispatcherBasics(): void
    {
        $dispatcher = new EventDispatcher();
        $called = false;

        $dispatcher->addEventListener(PreDenormalizeEvent::class, function() use (&$called) {
            $called = true;
        });

        $this->assertTrue($dispatcher->hasListeners(PreDenormalizeEvent::class));

        $event = new PreDenormalizeEvent(['id' => 1], EventTestClass::class);
        $dispatcher->dispatch($event);

        $this->assertTrue($called);
    }

    public function testPreDenormalizeEvent(): void
    {
        $mapper = new Mapper();
        $modified = false;

        $mapper->addEventListener(PreDenormalizeEvent::class, function(PreDenormalizeEvent $event) use (&$modified) {
            // Modify data before mapping
            $event->data['name'] = 'Modified';
            $modified = true;
        });

        $obj = $mapper->fromArray(['id' => 1, 'name' => 'Original'], EventTestClass::class);

        $this->assertTrue($modified);
        $this->assertSame('Modified', $obj->name);
    }

    public function testPostDenormalizeEvent(): void
    {
        $mapper = new Mapper();
        $executed = false;

        $mapper->addEventListener(PostDenormalizeEvent::class, function(PostDenormalizeEvent $event) use (&$executed) {
            $this->assertInstanceOf(EventTestClass::class, $event->object);
            $this->assertSame(1, $event->object->id);
            $executed = true;
        });

        $mapper->fromArray(['id' => 1, 'name' => 'Test'], EventTestClass::class);

        $this->assertTrue($executed);
    }

    public function testPreNormalizeEvent(): void
    {
        $mapper = new Mapper();
        $modified = false;

        $mapper->addEventListener(PreNormalizeEvent::class, function(PreNormalizeEvent $event) use (&$modified) {
            // Modify object before normalization
            assert($event->object instanceof EventTestClass);
            $event->object->name = 'Modified';
            $modified = true;
        });

        $obj = new EventTestClass();
        $obj->id = 1;
        $obj->name = 'Original';

        $data = $mapper->toArray($obj);

        $this->assertTrue($modified);
        $this->assertSame('Modified', $data['name']);
    }

    public function testPostNormalizeEvent(): void
    {
        $mapper = new Mapper();
        $modified = false;

        $mapper->addEventListener(PostNormalizeEvent::class, function(PostNormalizeEvent $event) use (&$modified) {
            // Modify output data
            $event->data['extra'] = 'added';
            $modified = true;
        });

        $obj = new EventTestClass();
        $obj->id = 1;
        $obj->name = 'Test';

        $data = $mapper->toArray($obj);

        $this->assertTrue($modified);
        $this->assertSame('added', $data['extra']);
    }

    public function testDenormalizationErrorEvent(): void
    {
        $mapper = new Mapper();
        $errorCaught = false;

        $mapper->addEventListener(DenormalizationErrorEvent::class, function(DenormalizationErrorEvent $event) use (&$errorCaught) {
            $this->assertInstanceOf(\Throwable::class, $event->exception);
            $errorCaught = true;
        });

        try {
            // Invalid data that should fail
            $mapper->fromArray(['id' => 'not-a-number'], EventTestClass::class);
        } catch (\Throwable $e) {
            // Expected
        }

        $this->assertTrue($errorCaught);
    }

    public function testEventPriority(): void
    {
        $dispatcher = new EventDispatcher();
        $order = [];

        $dispatcher->addEventListener(PreDenormalizeEvent::class, function() use (&$order) {
            $order[] = 'low';
        }, priority: 0);

        $dispatcher->addEventListener(PreDenormalizeEvent::class, function() use (&$order) {
            $order[] = 'high';
        }, priority: 100);

        $dispatcher->addEventListener(PreDenormalizeEvent::class, function() use (&$order) {
            $order[] = 'medium';
        }, priority: 50);

        $event = new PreDenormalizeEvent(['id' => 1], EventTestClass::class);
        $dispatcher->dispatch($event);

        $this->assertSame(['high', 'medium', 'low'], $order);
    }

    public function testStopPropagation(): void
    {
        $dispatcher = new EventDispatcher();
        $secondCalled = false;

        $dispatcher->addEventListener(PreDenormalizeEvent::class, function($event) {
            $event->stopPropagation();
        }, priority: 100);

        $dispatcher->addEventListener(PreDenormalizeEvent::class, function() use (&$secondCalled) {
            $secondCalled = true;
        }, priority: 0);

        $event = new PreDenormalizeEvent(['id' => 1], EventTestClass::class);
        $dispatcher->dispatch($event);

        $this->assertFalse($secondCalled, 'Second listener should not be called due to propagation stop');
    }

    public function testRemoveListener(): void
    {
        $dispatcher = new EventDispatcher();
        $called = false;

        $listener = function() use (&$called) {
            $called = true;
        };

        $dispatcher->addEventListener(PreDenormalizeEvent::class, $listener);
        $dispatcher->removeEventListener(PreDenormalizeEvent::class, $listener);

        $event = new PreDenormalizeEvent(['id' => 1], EventTestClass::class);
        $dispatcher->dispatch($event);

        $this->assertFalse($called);
    }
}

class EventTestClass
{
    public int $id;
    public string $name;
}
