<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Tests\Fixtures\TestClass;
use InvalidArgumentException;
use JsonException;

class MapperTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testFromArrayWithBasicTypes(): void
    {
        $data = ['id' => 1, 'name' => 'John Doe', 'active' => true, 'user_age' => 30, 'is_admin' => false];

        $object = $this->mapper->fromArray($data, TestClass::class);

        $this->assertInstanceOf(TestClass::class, $object);
        $this->assertSame(1, $object->getId());
        $this->assertSame('John Doe', $object->getName());
        $this->assertTrue($object->isActive());
        $this->assertSame(30, $object->getAge());
        $this->assertFalse($object->isAdmin());
    }

    public function testFromArrayWithStringInteger(): void
    {
        $data = ['id' => '42', 'name' => 'Test', 'active' => true, 'user_age' => '25', 'is_admin' => false];

        $object = $this->mapper->fromArray($data, TestClass::class);

        $this->assertSame(42, $object->getId());
        $this->assertSame(25, $object->getAge());
    }

    public function testFromArrayWithStringBoolean(): void
    {
        $data = ['id' => 1, 'name' => 'Test', 'active' => 'true', 'user_age' => 30, 'is_admin' => 'false'];

        $object = $this->mapper->fromArray($data, TestClass::class);

        $this->assertTrue($object->isActive());
        $this->assertFalse($object->isAdmin());
    }

    public function testFromArrayWithNumericBoolean(): void
    {
        $data = ['id' => 1, 'name' => 'Test', 'active' => 1, 'user_age' => 30, 'is_admin' => 0];

        $object = $this->mapper->fromArray($data, TestClass::class);

        $this->assertTrue($object->isActive());
        $this->assertFalse($object->isAdmin());
    }

    public function testFromArrayWithCustomPropertyName(): void
    {
        $data = ['id' => 1, 'name' => 'Test', 'active' => true, 'user_age' => 40, 'is_admin' => true];

        $object = $this->mapper->fromArray($data, TestClass::class);

        $this->assertSame(40, $object->getAge());
        $this->assertTrue($object->isAdmin());
    }

    public function testFromArrayIgnoresUnmappedProperties(): void
    {
        $data = ['id' => 1, 'name' => 'Test', 'active' => true, 'user_age' => 30, 'is_admin' => false, 'extra_field' => 'ignored'];

        $object = $this->mapper->fromArray($data, TestClass::class);

        $this->assertInstanceOf(TestClass::class, $object);
    }

    public function testFromArrayWithMissingFields(): void
    {
        $data = ['id' => 1, 'name' => 'Test'];

        $object = $this->mapper->fromArray($data, TestClass::class);

        $this->assertSame(1, $object->getId());
        $this->assertSame('Test', $object->getName());
    }

    public function testFromArrayThrowsExceptionOnInvalidIntegerCast(): void
    {
        $data = ['id' => 'not a number', 'name' => 'Test', 'active' => true, 'user_age' => 30, 'is_admin' => false];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot cast value of field 'id' to integer");

        $this->mapper->fromArray($data, TestClass::class);
    }

    public function testFromArrayThrowsExceptionOnInvalidBooleanCast(): void
    {
        $data = ['id' => 1, 'name' => 'Test', 'active' => 'invalid', 'user_age' => 30, 'is_admin' => false];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot cast value of field 'active' to boolean");

        $this->mapper->fromArray($data, TestClass::class);
    }

    public function testToArrayWithBasicTypes(): void
    {
        $object = new TestClass();
        $object->setId(1);
        $object->setName('Jane Doe');
        $object->setActive(false);
        $object->setAge(25);
        $object->setIsAdmin(true);
        $object->setUnmappedProperty('This should be in array');

        $data = $this->mapper->toArray($object);

        $this->assertSame(1, $data['id']);
        $this->assertSame('Jane Doe', $data['name']);
        $this->assertFalse($data['active']);
        $this->assertSame(25, $data['user_age']);
        $this->assertTrue($data['is_admin']);
        // All properties are serialized, even without MapProperty attribute
        $this->assertSame('This should be in array', $data['unmappedProperty']);
    }

    public function testToArrayWithCustomPropertyName(): void
    {
        $object = new TestClass();
        $object->setId(1);
        $object->setName('Test');
        $object->setActive(true);
        $object->setAge(50);
        $object->setIsAdmin(false);

        $data = $this->mapper->toArray($object);

        $this->assertArrayHasKey('user_age', $data);
        $this->assertArrayNotHasKey('age', $data);
        $this->assertSame(50, $data['user_age']);

        $this->assertArrayHasKey('is_admin', $data);
        $this->assertArrayNotHasKey('isAdmin', $data);
        $this->assertFalse($data['is_admin']);
    }

    public function testRoundTripConversion(): void
    {
        $originalData = ['id' => 123, 'name' => 'Round Trip', 'active' => true, 'user_age' => 35, 'is_admin' => false];

        $object = $this->mapper->fromArray($originalData, TestClass::class);
        $resultData = $this->mapper->toArray($object);

        $this->assertSame(123, $resultData['id']);
        $this->assertSame('Round Trip', $resultData['name']);
        $this->assertTrue($resultData['active']);
        $this->assertSame(35, $resultData['user_age']);
        $this->assertFalse($resultData['is_admin']);
    }

    public function testFromArrayHandlesZeroValues(): void
    {
        $data = ['id' => 0, 'name' => '', 'active' => false, 'user_age' => 0, 'is_admin' => false];

        $object = $this->mapper->fromArray($data, TestClass::class);

        $this->assertSame(0, $object->getId());
        $this->assertSame('', $object->getName());
        $this->assertFalse($object->isActive());
        $this->assertSame(0, $object->getAge());
    }

    public function testToArrayHandlesZeroValues(): void
    {
        $object = new TestClass();
        $object->setId(0);
        $object->setName('');
        $object->setActive(false);
        $object->setAge(0);
        $object->setIsAdmin(false);

        $data = $this->mapper->toArray($object);

        $this->assertSame(0, $data['id']);
        $this->assertSame('', $data['name']);
        $this->assertFalse($data['active']);
        $this->assertSame(0, $data['user_age']);
        $this->assertFalse($data['is_admin']);
    }

    public function testToArrayPreservesTypes(): void
    {
        $object = new TestClass();
        $object->setId(100);
        $object->setName('Type Test');
        $object->setActive(true);
        $object->setAge(30);
        $object->setIsAdmin(false);

        $data = $this->mapper->toArray($object);

        $this->assertIsInt($data['id']);
        $this->assertIsString($data['name']);
        $this->assertIsBool($data['active']);
        $this->assertIsInt($data['user_age']);
        $this->assertIsBool($data['is_admin']);
    }

    public function testFromArrayWithExtraProperties(): void
    {
        $data = [
            'id' => 200,
            'name' => 'Extra Props',
            'active' => true,
            'user_age' => 25,
            'is_admin' => true,
            'extra1' => 'ignored',
            'extra2' => 123,
            'extra3' => false
        ];

        $object = $this->mapper->fromArray($data, TestClass::class);

        $this->assertSame(200, $object->getId());
        $this->assertSame('Extra Props', $object->getName());
        $this->assertTrue($object->isActive());
        $this->assertSame(25, $object->getAge());
        $this->assertTrue($object->isAdmin());
    }

    // JSON-specific tests
    public function testFromJsonThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(JsonException::class);

        $this->mapper->fromJson('invalid json', TestClass::class);
    }

    public function testFromJsonWorksWithValidJson(): void
    {
        $data = ['id' => 300, 'name' => 'JSON Test', 'active' => true, 'user_age' => 40, 'is_admin' => false];
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $object = $this->mapper->fromJson($json, TestClass::class);

        $this->assertSame(300, $object->getId());
        $this->assertSame('JSON Test', $object->getName());
        $this->assertTrue($object->isActive());
        $this->assertSame(40, $object->getAge());
        $this->assertFalse($object->isAdmin());
    }

    public function testToJsonProducesValidJson(): void
    {
        $object = new TestClass();
        $object->setId(400);
        $object->setName('JSON Output');
        $object->setActive(false);
        $object->setAge(50);
        $object->setIsAdmin(true);

        $json = $this->mapper->toJson($object);

        $this->assertJson($json);

        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertSame(400, $data['id']);
        $this->assertSame('JSON Output', $data['name']);
        $this->assertFalse($data['active']);
        $this->assertSame(50, $data['user_age']);
        $this->assertTrue($data['is_admin']);
    }

    public function testJsonRoundTrip(): void
    {
        $originalData = ['id' => 500, 'name' => 'JSON Round', 'active' => true, 'user_age' => 60, 'is_admin' => false];
        $json1 = json_encode($originalData, JSON_THROW_ON_ERROR);

        $object = $this->mapper->fromJson($json1, TestClass::class);
        $json2 = $this->mapper->toJson($object);
        $resultData = json_decode($json2, true);

        $this->assertIsArray($resultData);
        $this->assertSame(500, $resultData['id']);
        $this->assertSame('JSON Round', $resultData['name']);
        $this->assertTrue($resultData['active']);
        $this->assertSame(60, $resultData['user_age']);
        $this->assertFalse($resultData['is_admin']);
    }
}
