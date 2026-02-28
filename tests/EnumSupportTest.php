<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Tests\Fixtures\TaskWithEnums;
use Tests\Fixtures\Status;
use Tests\Fixtures\Priority;
use InvalidArgumentException;

class EnumSupportTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testFromArrayWithBackedEnum(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Task',
            'status' => 'active',
            'priority' => 'High'
        ];

        $object = $this->mapper->fromArray($data, TaskWithEnums::class);

        $this->assertInstanceOf(TaskWithEnums::class, $object);
        $this->assertSame(1, $object->getId());
        $this->assertSame('Test Task', $object->getName());
        $this->assertSame(Status::Active, $object->getStatus());
        $this->assertSame(Priority::High, $object->getPriority());
    }

    public function testFromArrayWithNullableEnum(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Task',
            'status' => 'pending',
            'priority' => 'Low',
            'optionalStatus' => null
        ];

        $object = $this->mapper->fromArray($data, TaskWithEnums::class);

        $this->assertNull($object->getOptionalStatus());
    }

    public function testFromArrayWithNullableEnumSet(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Task',
            'status' => 'active',
            'priority' => 'Medium',
            'optionalStatus' => 'inactive'
        ];

        $object = $this->mapper->fromArray($data, TaskWithEnums::class);

        $this->assertSame(Status::Inactive, $object->getOptionalStatus());
    }

    public function testFromArrayWithInvalidBackedEnumValue(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Task',
            'status' => 'invalid_status',
            'priority' => 'High'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid value for field 'status'");

        $this->mapper->fromArray($data, TaskWithEnums::class);
    }

    public function testFromArrayWithInvalidUnitEnumValue(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Test Task',
            'status' => 'active',
            'priority' => 'InvalidPriority'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid value for field 'priority'");

        $this->mapper->fromArray($data, TaskWithEnums::class);
    }

    public function testToArrayWithBackedEnum(): void
    {
        $object = new TaskWithEnums();
        $object->setId(1);
        $object->setName('Test Task');
        $object->setStatus(Status::Pending);
        $object->setPriority(Priority::Critical);

        $data = $this->mapper->toArray($object);

        $this->assertSame(1, $data['id']);
        $this->assertSame('Test Task', $data['name']);
        $this->assertSame('pending', $data['status']);
        $this->assertSame('Critical', $data['priority']);
    }

    public function testToArrayWithNullableEnum(): void
    {
        $object = new TaskWithEnums();
        $object->setId(1);
        $object->setName('Test Task');
        $object->setStatus(Status::Active);
        $object->setPriority(Priority::Low);
        $object->setOptionalStatus(null);

        $data = $this->mapper->toArray($object);

        $this->assertArrayHasKey('optionalStatus', $data);
        $this->assertNull($data['optionalStatus']);
    }

    public function testToArrayWithNullableEnumSet(): void
    {
        $object = new TaskWithEnums();
        $object->setId(1);
        $object->setName('Test Task');
        $object->setStatus(Status::Active);
        $object->setPriority(Priority::Low);
        $object->setOptionalStatus(Status::Inactive);

        $data = $this->mapper->toArray($object);

        $this->assertSame('inactive', $data['optionalStatus']);
    }

    public function testRoundTripWithEnums(): void
    {
        $originalData = [
            'id' => 123,
            'name' => 'Round Trip Task',
            'status' => 'active',
            'priority' => 'High',
            'optionalStatus' => 'pending'
        ];

        $object = $this->mapper->fromArray($originalData, TaskWithEnums::class);
        $resultData = $this->mapper->toArray($object);

        $this->assertSame(123, $resultData['id']);
        $this->assertSame('Round Trip Task', $resultData['name']);
        $this->assertSame('active', $resultData['status']);
        $this->assertSame('High', $resultData['priority']);
        $this->assertSame('pending', $resultData['optionalStatus']);
    }

    public function testFromJsonWithEnums(): void
    {
        $data = [
            'id' => 456,
            'name' => 'JSON Task',
            'status' => 'inactive',
            'priority' => 'Medium'
        ];
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $object = $this->mapper->fromJson($json, TaskWithEnums::class);

        $this->assertSame(456, $object->getId());
        $this->assertSame('JSON Task', $object->getName());
        $this->assertSame(Status::Inactive, $object->getStatus());
        $this->assertSame(Priority::Medium, $object->getPriority());
    }

    public function testToJsonWithEnums(): void
    {
        $object = new TaskWithEnums();
        $object->setId(789);
        $object->setName('JSON Output Task');
        $object->setStatus(Status::Pending);
        $object->setPriority(Priority::Low);

        $json = $this->mapper->toJson($object);

        $this->assertJson($json);

        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertSame(789, $data['id']);
        $this->assertSame('JSON Output Task', $data['name']);
        $this->assertSame('pending', $data['status']);
        $this->assertSame('Low', $data['priority']);
    }

    public function testAllBackedEnumValues(): void
    {
        foreach (['active', 'inactive', 'pending'] as $statusValue) {
            $data = [
                'id' => 1,
                'name' => 'Test',
                'status' => $statusValue,
                'priority' => 'High'
            ];

            $object = $this->mapper->fromArray($data, TaskWithEnums::class);
            $this->assertSame($statusValue, $object->getStatus()->value);
        }
    }

    public function testAllUnitEnumValues(): void
    {
        foreach (['Low', 'Medium', 'High', 'Critical'] as $priorityName) {
            $data = [
                'id' => 1,
                'name' => 'Test',
                'status' => 'active',
                'priority' => $priorityName
            ];

            $object = $this->mapper->fromArray($data, TaskWithEnums::class);
            $this->assertSame($priorityName, $object->getPriority()->name);
        }
    }
}
