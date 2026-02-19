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
        // Default output format is ISO 8601
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $data['customDate']);
    }

    public function testToArrayWithOutputFormatDateOnly(): void
    {
        $object = new OutputFormatDateOnlyClass();
        $object->id = 1;
        $object->createdAt = new DateTimeImmutable('2024-12-15 18:30:00');

        $data = $this->mapper->toArray($object);

        $this->assertSame(1, $data['id']);
        $this->assertSame('2024-12-15', $data['createdAt']);
    }

    public function testToArrayWithOutputFormatDateTimeNoTimezone(): void
    {
        $object = new OutputFormatDateTimeClass();
        $object->id = 1;
        $object->createdAt = new DateTimeImmutable('2024-12-15 18:30:00');

        $data = $this->mapper->toArray($object);

        $this->assertSame(1, $data['id']);
        $this->assertSame('2024-12-15 18:30:00', $data['createdAt']);
    }

    public function testToArrayWithOutputFormatAtom(): void
    {
        $object = new OutputFormatAtomClass();
        $object->id = 1;
        $object->createdAt = new DateTimeImmutable('2024-12-15 18:30:00', new \DateTimeZone('UTC'));

        $data = $this->mapper->toArray($object);

        $this->assertSame(1, $data['id']);
        $this->assertSame('2024-12-15T18:30:00+00:00', $data['createdAt']);
    }

    public function testToJsonWithOutputFormat(): void
    {
        $object = new OutputFormatDateOnlyClass();
        $object->id = 1;
        $object->createdAt = new DateTimeImmutable('2024-12-15 18:30:00');

        $json = $this->mapper->toJson($object);
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true);

        $this->assertSame('2024-12-15', $data['createdAt']);
    }

    public function testInputAndOutputFormatCanDiffer(): void
    {
        // Input in custom format, output in different custom format
        $input = ['id' => 1, 'eventDate' => '15/12/2024 18:30'];
        $object = $this->mapper->fromArray($input, DifferentFormatsClass::class);

        $this->assertSame('15/12/2024 18:30', $object->eventDate->format('d/m/Y H:i'));

        $data = $this->mapper->toArray($object);
        $this->assertSame('2024-12-15', $data['eventDate']);
    }

    public function testOutputFormatWithoutMapDateTimeProperty(): void
    {
        // DateTime property WITHOUT MapDateTimeProperty → default ISO 8601 output
        $object = new NoAttributeDateTimeClass();
        $object->id = 1;
        $object->createdAt = new DateTimeImmutable('2024-12-15 18:30:00');

        $data = $this->mapper->toArray($object);

        $this->assertIsString($data['createdAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $data['createdAt']);
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

class OutputFormatDateOnlyClass
{
    public int $id;

    #[MapDateTimeProperty(outputFormat: 'Y-m-d')]
    public DateTimeImmutable $createdAt;
}

class OutputFormatDateTimeClass
{
    public int $id;

    #[MapDateTimeProperty(outputFormat: 'Y-m-d H:i:s')]
    public DateTimeImmutable $createdAt;
}

class OutputFormatAtomClass
{
    public int $id;

    #[MapDateTimeProperty(outputFormat: DateTimeInterface::ATOM)]
    public DateTimeImmutable $createdAt;
}

class DifferentFormatsClass
{
    public int $id;

    #[MapDateTimeProperty(format: 'd/m/Y H:i', outputFormat: 'Y-m-d')]
    public DateTimeImmutable $eventDate;
}

class NoAttributeDateTimeClass
{
    public int $id;

    public DateTimeImmutable $createdAt;
}
