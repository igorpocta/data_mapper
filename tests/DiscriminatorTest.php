<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Exceptions\ValidationException;
use Tests\Fixtures\Vehicle;
use Tests\Fixtures\Car;
use Tests\Fixtures\Bike;
use Tests\Fixtures\Truck;
use Tests\Fixtures\Event;
use Tests\Fixtures\UserCreatedEvent;
use Tests\Fixtures\OrderPlacedEvent;

class DiscriminatorTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testDiscriminatorMapsToCarClass(): void
    {
        $data = [
            'type' => 'car',
            'brand' => 'Toyota',
            'year' => 2020,
            'doors' => 4,
            'convertible' => false
        ];

        $vehicle = $this->mapper->fromArray($data, Vehicle::class);

        $this->assertInstanceOf(Car::class, $vehicle);
        $this->assertSame('car', $vehicle->getType());
        $this->assertSame('Toyota', $vehicle->getBrand());
        $this->assertSame(2020, $vehicle->getYear());
        $this->assertSame(4, $vehicle->getDoors());
        $this->assertFalse($vehicle->isConvertible());
    }

    public function testDiscriminatorMapsToBikeClass(): void
    {
        $data = [
            'type' => 'bike',
            'brand' => 'Trek',
            'year' => 2021,
            'electric' => true,
            'gears' => 21
        ];

        $vehicle = $this->mapper->fromArray($data, Vehicle::class);

        $this->assertInstanceOf(Bike::class, $vehicle);
        $this->assertSame('bike', $vehicle->getType());
        $this->assertSame('Trek', $vehicle->getBrand());
        $this->assertSame(2021, $vehicle->getYear());
        $this->assertTrue($vehicle->isElectric());
        $this->assertSame(21, $vehicle->getGears());
    }

    public function testDiscriminatorMapsToTruckClass(): void
    {
        $data = [
            'type' => 'truck',
            'brand' => 'Ford',
            'year' => 2019,
            'capacity' => 5000,
            'fourWheelDrive' => true
        ];

        $vehicle = $this->mapper->fromArray($data, Vehicle::class);

        $this->assertInstanceOf(Truck::class, $vehicle);
        $this->assertSame('truck', $vehicle->getType());
        $this->assertSame('Ford', $vehicle->getBrand());
        $this->assertSame(2019, $vehicle->getYear());
        $this->assertSame(5000, $vehicle->getCapacity());
        $this->assertTrue($vehicle->isFourWheelDrive());
    }

    public function testDiscriminatorThrowsOnMissingProperty(): void
    {
        $data = [
            'brand' => 'Toyota',
            'year' => 2020,
            'doors' => 4
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Missing discriminator property 'type'");

        $this->mapper->fromArray($data, Vehicle::class);
    }

    public function testDiscriminatorThrowsOnUnknownValue(): void
    {
        $data = [
            'type' => 'airplane',
            'brand' => 'Boeing',
            'year' => 2022
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Unknown discriminator value 'airplane'");
        $this->expectExceptionMessage("Available values: car, bike, truck");

        $this->mapper->fromArray($data, Vehicle::class);
    }

    public function testDiscriminatorWithJsonMapping(): void
    {
        $json = '{"type":"car","brand":"Honda","year":2021,"doors":2,"convertible":true}';

        $vehicle = $this->mapper->fromJson($json, Vehicle::class);

        $this->assertInstanceOf(Car::class, $vehicle);
        $this->assertSame('Honda', $vehicle->getBrand());
        $this->assertSame(2, $vehicle->getDoors());
        $this->assertTrue($vehicle->isConvertible());
    }

    public function testDiscriminatorWithCustomPropertyName(): void
    {
        $data = [
            'event_type' => 'user_created',
            'timestamp' => '2025-01-01T12:00:00Z',
            'userId' => 123,
            'email' => 'user@example.com'
        ];

        $event = $this->mapper->fromArray($data, Event::class);

        $this->assertInstanceOf(UserCreatedEvent::class, $event);
        $this->assertSame('user_created', $event->getEventType());
        $this->assertSame('2025-01-01T12:00:00Z', $event->getTimestamp());
        $this->assertSame(123, $event->getUserId());
        $this->assertSame('user@example.com', $event->getEmail());
    }

    public function testDiscriminatorWithAnotherCustomType(): void
    {
        $data = [
            'event_type' => 'order_placed',
            'timestamp' => '2025-01-02T14:30:00Z',
            'orderId' => 456,
            'total' => 199.99
        ];

        $event = $this->mapper->fromArray($data, Event::class);

        $this->assertInstanceOf(OrderPlacedEvent::class, $event);
        $this->assertSame('order_placed', $event->getEventType());
        $this->assertSame('2025-01-02T14:30:00Z', $event->getTimestamp());
        $this->assertSame(456, $event->getOrderId());
        $this->assertSame(199.99, $event->getTotal());
    }

    public function testDiscriminatorWorksWithCollection(): void
    {
        $data = [
            [
                'type' => 'car',
                'brand' => 'Toyota',
                'year' => 2020,
                'doors' => 4,
                'convertible' => false
            ],
            [
                'type' => 'bike',
                'brand' => 'Trek',
                'year' => 2021,
                'electric' => true,
                'gears' => 21
            ],
            [
                'type' => 'truck',
                'brand' => 'Ford',
                'year' => 2019,
                'capacity' => 5000,
                'fourWheelDrive' => true
            ]
        ];

        $vehicles = $this->mapper->fromArrayCollection($data, Vehicle::class);

        $this->assertCount(3, $vehicles);
        $this->assertInstanceOf(Car::class, $vehicles[0]);
        $this->assertInstanceOf(Bike::class, $vehicles[1]);
        $this->assertInstanceOf(Truck::class, $vehicles[2]);
    }

    public function testDiscriminatorWithToArray(): void
    {
        $data = [
            'type' => 'car',
            'brand' => 'BMW',
            'year' => 2022,
            'doors' => 2,
            'convertible' => true
        ];

        $vehicle = $this->mapper->fromArray($data, Vehicle::class);
        $result = $this->mapper->toArray($vehicle);

        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('brand', $result);
        $this->assertArrayHasKey('year', $result);
        $this->assertArrayHasKey('doors', $result);
        $this->assertArrayHasKey('convertible', $result);
        $this->assertSame('car', $result['type']);
        $this->assertSame('BMW', $result['brand']);
        $this->assertSame(2022, $result['year']);
        $this->assertSame(2, $result['doors']);
        $this->assertTrue($result['convertible']);
    }

    public function testDiscriminatorRoundTrip(): void
    {
        $originalData = [
            'type' => 'bike',
            'brand' => 'Giant',
            'year' => 2023,
            'electric' => false,
            'gears' => 18
        ];

        // Array -> Object
        $vehicle = $this->mapper->fromArray($originalData, Vehicle::class);

        // Object -> Array
        $resultData = $this->mapper->toArray($vehicle);

        // Verify all data preserved
        $this->assertSame('bike', $resultData['type']);
        $this->assertSame('Giant', $resultData['brand']);
        $this->assertSame(2023, $resultData['year']);
        $this->assertFalse($resultData['electric']);
        $this->assertSame(18, $resultData['gears']);
    }

    public function testDiscriminatorWithTypeConversion(): void
    {
        $data = [
            'type' => 'car',
            'brand' => 'Audi',
            'year' => '2021', // String instead of int
            'doors' => '4', // String instead of int
            'convertible' => 'false' // String instead of bool
        ];

        $vehicle = $this->mapper->fromArray($data, Vehicle::class);

        $this->assertInstanceOf(Car::class, $vehicle);
        $this->assertSame(2021, $vehicle->getYear());
        $this->assertSame(4, $vehicle->getDoors());
        $this->assertFalse($vehicle->isConvertible());
    }

    public function testDiscriminatorWithPartialData(): void
    {
        // Using defaults for optional properties
        $data = [
            'type' => 'bike',
            'brand' => 'Cannondale',
            'year' => 2020
            // electric and gears will use defaults
        ];

        $vehicle = $this->mapper->fromArray($data, Vehicle::class);

        $this->assertInstanceOf(Bike::class, $vehicle);
        $this->assertSame('Cannondale', $vehicle->getBrand());
        $this->assertSame(2020, $vehicle->getYear());
        $this->assertFalse($vehicle->isElectric()); // default
        $this->assertSame(1, $vehicle->getGears()); // default
    }

    public function testDiscriminatorCollectionWithDifferentTypes(): void
    {
        $json = '[
            {"type":"car","brand":"Tesla","year":2023,"doors":4,"convertible":false},
            {"type":"bike","brand":"Specialized","year":2022,"electric":true,"gears":12}
        ]';

        $vehicles = $this->mapper->fromJsonCollection($json, Vehicle::class);

        $this->assertCount(2, $vehicles);
        $this->assertInstanceOf(Car::class, $vehicles[0]);
        $this->assertInstanceOf(Bike::class, $vehicles[1]);
        $this->assertSame('Tesla', $vehicles[0]->getBrand());
        $this->assertSame('Specialized', $vehicles[1]->getBrand());
    }

    public function testDiscriminatorThrowsOnInvalidValueType(): void
    {
        // Discriminator value is an array (not string or int)
        $data = [
            'type' => ['invalid', 'array'],
            'brand' => 'Toyota',
            'year' => 2020
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Discriminator value must be string or int, array given');

        $this->mapper->fromArray($data, Vehicle::class);
    }
}
