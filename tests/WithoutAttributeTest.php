<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Tests\Fixtures\SimpleClass;

class WithoutAttributeTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testFromArrayWithoutMapPropertyAttribute(): void
    {
        $data = ['id' => 1, 'title' => 'Test Title', 'enabled' => true, 'note' => 'A note'];

        $object = $this->mapper->fromArray($data, SimpleClass::class);

        $this->assertInstanceOf(SimpleClass::class, $object);
        $this->assertSame(1, $object->getId());
        $this->assertSame('Test Title', $object->getTitle());
        $this->assertTrue($object->isEnabled());
        $this->assertSame('A note', $object->getNote());
    }

    public function testFromArrayWithoutAttributeAndNullableProperty(): void
    {
        $data = ['id' => 2, 'title' => 'Another Title', 'enabled' => false, 'note' => null];

        $object = $this->mapper->fromArray($data, SimpleClass::class);

        $this->assertSame(2, $object->getId());
        $this->assertSame('Another Title', $object->getTitle());
        $this->assertFalse($object->isEnabled());
        $this->assertNull($object->getNote());
    }

    public function testFromArrayWithMissingOptionalProperty(): void
    {
        $data = ['id' => 3, 'title' => 'Title Without Note', 'enabled' => true];

        $object = $this->mapper->fromArray($data, SimpleClass::class);

        $this->assertSame(3, $object->getId());
        $this->assertSame('Title Without Note', $object->getTitle());
        $this->assertTrue($object->isEnabled());
    }

    public function testToArrayWithoutMapPropertyAttribute(): void
    {
        $object = new SimpleClass();
        $object->setId(100);
        $object->setTitle('Export Test');
        $object->setEnabled(false);
        $object->setNote('Test note');

        $data = $this->mapper->toArray($object);

        $this->assertSame(100, $data['id']);
        $this->assertSame('Export Test', $data['title']);
        $this->assertFalse($data['enabled']);
        $this->assertSame('Test note', $data['note']);
    }

    public function testToArrayWithoutAttributeAndNullValue(): void
    {
        $object = new SimpleClass();
        $object->setId(101);
        $object->setTitle('Title Only');
        $object->setEnabled(true);
        $object->setNote(null);

        $data = $this->mapper->toArray($object);

        $this->assertSame(101, $data['id']);
        $this->assertSame('Title Only', $data['title']);
        $this->assertTrue($data['enabled']);
        // Null values are not included by default
        $this->assertArrayNotHasKey('note', $data);
    }

    public function testRoundTripWithoutAttribute(): void
    {
        $originalData = ['id' => 200, 'title' => 'Round Trip', 'enabled' => true, 'note' => 'Note here'];

        $object = $this->mapper->fromArray($originalData, SimpleClass::class);
        $resultData = $this->mapper->toArray($object);

        $this->assertSame(200, $resultData['id']);
        $this->assertSame('Round Trip', $resultData['title']);
        $this->assertTrue($resultData['enabled']);
        $this->assertSame('Note here', $resultData['note']);
    }

    public function testFromArrayWithDifferentTypes(): void
    {
        $data = ['id' => 300, 'title' => 'Array Test', 'enabled' => false, 'note' => 'From array'];

        $object = $this->mapper->fromArray($data, SimpleClass::class);

        $this->assertSame(300, $object->getId());
        $this->assertSame('Array Test', $object->getTitle());
        $this->assertFalse($object->isEnabled());
        $this->assertSame('From array', $object->getNote());
    }

    public function testToArrayWithAllProperties(): void
    {
        $object = new SimpleClass();
        $object->setId(400);
        $object->setTitle('To Array');
        $object->setEnabled(true);
        $object->setNote('Array note');

        $data = $this->mapper->toArray($object);

        $this->assertSame(400, $data['id']);
        $this->assertSame('To Array', $data['title']);
        $this->assertTrue($data['enabled']);
        $this->assertSame('Array note', $data['note']);
    }

    public function testFromArrayWithTypeCoercion(): void
    {
        $data = ['id' => '500', 'title' => 'Type Coercion', 'enabled' => 1, 'note' => 'Testing'];

        $object = $this->mapper->fromArray($data, SimpleClass::class);

        $this->assertSame(500, $object->getId());
        $this->assertSame('Type Coercion', $object->getTitle());
        $this->assertTrue($object->isEnabled());
        $this->assertSame('Testing', $object->getNote());
    }

    public function testToArrayPreservesTypes(): void
    {
        $object = new SimpleClass();
        $object->setId(600);
        $object->setTitle('Type Preservation');
        $object->setEnabled(false);
        $object->setNote('Notes');

        $data = $this->mapper->toArray($object);

        $this->assertIsInt($data['id']);
        $this->assertIsString($data['title']);
        $this->assertIsBool($data['enabled']);
        $this->assertIsString($data['note']);
    }

    public function testFromJsonStillWorks(): void
    {
        $data = ['id' => 700, 'title' => 'JSON Test', 'enabled' => true, 'note' => 'JSON note'];
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $object = $this->mapper->fromJson($json, SimpleClass::class);

        $this->assertSame(700, $object->getId());
        $this->assertSame('JSON Test', $object->getTitle());
        $this->assertTrue($object->isEnabled());
        $this->assertSame('JSON note', $object->getNote());
    }

    public function testToJsonStillWorks(): void
    {
        $object = new SimpleClass();
        $object->setId(800);
        $object->setTitle('JSON Output');
        $object->setEnabled(false);
        $object->setNote('Output note');

        $json = $this->mapper->toJson($object);
        $data = json_decode($json, true);

        $this->assertIsArray($data);
        $this->assertSame(800, $data['id']);
        $this->assertSame('JSON Output', $data['title']);
        $this->assertFalse($data['enabled']);
        $this->assertSame('Output note', $data['note']);
    }
}
