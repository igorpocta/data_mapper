<?php

declare(strict_types=1);

namespace Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Exceptions\ValidationException;
use Tests\Fixtures\EventWithDateTime;

class DateTimeTypeTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testFromArrayWithISO8601DateTime(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Event',
            'createdAt' => '2024-10-28T10:30:00+00:00',
        ];

        $object = $this->mapper->fromArray($data, EventWithDateTime::class);

        $this->assertInstanceOf(EventWithDateTime::class, $object);
        $this->assertInstanceOf(DateTimeImmutable::class, $object->getCreatedAt());
        $this->assertSame('2024-10-28', $object->getCreatedAt()->format('Y-m-d'));
        $this->assertSame('10:30:00', $object->getCreatedAt()->format('H:i:s'));
    }

    public function testFromArrayWithMySQLDateTime(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Event',
            'createdAt' => '2024-10-28 10:30:00',
        ];

        $object = $this->mapper->fromArray($data, EventWithDateTime::class);

        $this->assertInstanceOf(DateTimeImmutable::class, $object->getCreatedAt());
        $this->assertSame('2024-10-28 10:30:00', $object->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    public function testFromArrayWithDateOnly(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Event',
            'createdAt' => '2024-10-28',
        ];

        $object = $this->mapper->fromArray($data, EventWithDateTime::class);

        $this->assertInstanceOf(DateTimeImmutable::class, $object->getCreatedAt());
        $this->assertSame('2024-10-28', $object->getCreatedAt()->format('Y-m-d'));
    }

    public function testFromArrayWithCustomFormat(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Event',
            'createdAt' => '2024-10-28T10:30:00+00:00',
            'scheduledAt' => '28/10/2024 15:30',
        ];

        $object = $this->mapper->fromArray($data, EventWithDateTime::class);

        $this->assertInstanceOf(DateTimeImmutable::class, $object->getScheduledAt());
        $this->assertSame('28/10/2024 15:30', $object->getScheduledAt()->format('d/m/Y H:i'));
    }

    public function testFromArrayWithNullableDateTime(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Event',
            'createdAt' => '2024-10-28T10:30:00+00:00',
            'updatedAt' => null,
        ];

        $object = $this->mapper->fromArray($data, EventWithDateTime::class);

        $this->assertNull($object->getUpdatedAt());
    }

    public function testFromArrayWithNullableDateTimeSet(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Event',
            'createdAt' => '2024-10-28T10:30:00+00:00',
            'updatedAt' => '2024-10-28T12:00:00+00:00',
        ];

        $object = $this->mapper->fromArray($data, EventWithDateTime::class);

        $this->assertInstanceOf(DateTimeImmutable::class, $object->getUpdatedAt());
        $this->assertSame('2024-10-28', $object->getUpdatedAt()->format('Y-m-d'));
    }

    public function testToArrayWithDateTime(): void
    {
        $object = new EventWithDateTime();
        $object->setId(1);
        $object->setName('Test Event');
        $object->setCreatedAt(new DateTimeImmutable('2024-10-28 10:30:00'));
        $object->setUpdatedAt(new DateTimeImmutable('2024-10-28 12:00:00'));

        $data = $this->mapper->toArray($object);

        $this->assertIsString($data['createdAt']);
        $this->assertIsString($data['updatedAt']);
        // Check that it's in ISO 8601 format with microseconds
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+[+-]\d{2}:\d{2}$/', $data['createdAt']);
    }

    public function testToArrayWithNullableDateTime(): void
    {
        $object = new EventWithDateTime();
        $object->setId(1);
        $object->setName('Test Event');
        $object->setCreatedAt(new DateTimeImmutable('2024-10-28 10:30:00'));
        $object->setUpdatedAt(null);

        $data = $this->mapper->toArray($object);

        $this->assertArrayHasKey('updatedAt', $data);
        $this->assertNull($data['updatedAt']);
    }

    public function testRoundTripWithDateTime(): void
    {
        $originalData = [
            'id' => 1,
            'name' => 'Test Event',
            'createdAt' => '2024-10-28T10:30:00+00:00',
            'updatedAt' => '2024-10-28T12:00:00+00:00',
        ];

        $object = $this->mapper->fromArray($originalData, EventWithDateTime::class);
        $resultData = $this->mapper->toArray($object);

        $this->assertSame(1, $resultData['id']);
        $this->assertSame('Test Event', $resultData['name']);
        $this->assertIsString($resultData['createdAt']);
        $this->assertIsString($resultData['updatedAt']);
    }

    public function testFromArrayWithInvalidDateTime(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Event',
            'createdAt' => 'invalid-date',
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("invalid datetime format");

        $this->mapper->fromArray($data, EventWithDateTime::class);
    }

    public function testFromJsonWithDateTime(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Event',
            'createdAt' => '2024-10-28T10:30:00+00:00',
        ];
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $object = $this->mapper->fromJson($json, EventWithDateTime::class);

        $this->assertInstanceOf(EventWithDateTime::class, $object);
        $this->assertInstanceOf(DateTimeImmutable::class, $object->getCreatedAt());
    }

    public function testToJsonWithDateTime(): void
    {
        $object = new EventWithDateTime();
        $object->setId(1);
        $object->setName('Test Event');
        $object->setCreatedAt(new DateTimeImmutable('2024-10-28 10:30:00'));

        $json = $this->mapper->toJson($object);

        $this->assertJson($json);

        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertIsString($data['createdAt']);
    }
}
