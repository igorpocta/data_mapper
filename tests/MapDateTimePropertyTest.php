<?php

declare(strict_types=1);

namespace Tests;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Attributes\MapDateTimeProperty;
use Pocta\DataMapper\Attributes\PropertyType;
use Pocta\DataMapper\Mapper;

class MapDateTimePropertyTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testMapDateTimePropertyWithCustomFormat(): void
    {
        $data = [
            'id' => 1,
            'customDate' => '15/12/2024 18:30',
        ];

        $object = $this->mapper->fromArray($data, CustomDateFormatClass::class);

        $this->assertSame(1, $object->id);
        $this->assertInstanceOf(DateTimeInterface::class, $object->customDate);
        $this->assertSame('15/12/2024 18:30', $object->customDate->format('d/m/Y H:i'));
    }

    public function testMapDateTimePropertyWithTimezone(): void
    {
        $data = [
            'id' => 1,
            'eventTime' => '2024-12-15 18:30:00',
        ];

        $object = $this->mapper->fromArray($data, TimezoneTestClass::class);

        $this->assertSame(1, $object->id);
        $this->assertInstanceOf(DateTimeImmutable::class, $object->eventTime);
        $this->assertSame('Europe/Prague', $object->eventTime->getTimezone()->getName());
    }

    public function testMapDateTimePropertyWithCustomName(): void
    {
        $data = [
            'id' => 1,
            'created_timestamp' => '2024-12-15T10:00:00+00:00',
        ];

        $object = $this->mapper->fromArray($data, CustomNameClass::class);

        $this->assertSame(1, $object->id);
        $this->assertInstanceOf(DateTimeImmutable::class, $object->createdAt);
        $this->assertSame('2024-12-15', $object->createdAt->format('Y-m-d'));
    }

    public function testMapDateTimePropertyWithTypeOverride(): void
    {
        $data = [
            'id' => 1,
            'scheduledDate' => '2024-12-15T10:00:00+00:00',
        ];

        $object = $this->mapper->fromArray($data, TypeOverrideClass::class);

        $this->assertSame(1, $object->id);
        $this->assertInstanceOf(DateTimeImmutable::class, $object->scheduledDate);
    }

    public function testToArrayWithMapDateTimeProperty(): void
    {
        $object = new CustomDateFormatClass();
        $object->id = 1;
        $object->customDate = new DateTimeImmutable('2024-12-15 18:30:00');

        $data = $this->mapper->toArray($object);

        $this->assertSame(1, $data['id']);
        $this->assertIsString($data['customDate']);
        // Output format is always ISO 8601
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $data['customDate']);
    }
}

class CustomDateFormatClass
{
    public int $id;

    #[MapDateTimeProperty(format: 'd/m/Y H:i')]
    public DateTimeInterface $customDate;
}

class TimezoneTestClass
{
    public int $id;

    #[MapDateTimeProperty(timezone: 'Europe/Prague')]
    public DateTimeImmutable $eventTime;
}

class CustomNameClass
{
    public int $id;

    #[MapDateTimeProperty(name: 'created_timestamp')]
    public DateTimeImmutable $createdAt;
}

class TypeOverrideClass
{
    public int $id;

    #[MapDateTimeProperty(type: PropertyType::DateTimeImmutable)]
    public DateTimeInterface $scheduledDate;
}
