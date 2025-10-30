<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\MapperOptions;
use Pocta\DataMapper\Exceptions\ValidationException;
use Tests\Fixtures\TestClass;
use Tests\Fixtures\UserWithConstructor;
use InvalidArgumentException;

class BatchProcessingTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testFromArrayCollectionWithEmptyArray(): void
    {
        $result = $this->mapper->fromArrayCollection([], TestClass::class);

        $this->assertCount(0, $result);
    }

    public function testFromArrayCollectionWithSingleItem(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false]
        ];

        $result = $this->mapper->fromArrayCollection($data, TestClass::class);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(TestClass::class, $result[0]);
        $this->assertSame(1, $result[0]->getId());
        $this->assertSame('John', $result[0]->getName());
    }

    public function testFromArrayCollectionWithMultipleItems(): void
    {
        $data = [
            ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false],
            ['id' => 2, 'name' => 'Jane', 'active' => false, 'user_age' => 25, 'is_admin' => true],
            ['id' => 3, 'name' => 'Bob', 'active' => true, 'user_age' => 35, 'is_admin' => false],
        ];

        $result = $this->mapper->fromArrayCollection($data, TestClass::class);

        $this->assertCount(3, $result);

        $this->assertInstanceOf(TestClass::class, $result[0]);
        $this->assertSame(1, $result[0]->getId());
        $this->assertSame('John', $result[0]->getName());

        $this->assertInstanceOf(TestClass::class, $result[1]);
        $this->assertSame(2, $result[1]->getId());
        $this->assertSame('Jane', $result[1]->getName());

        $this->assertInstanceOf(TestClass::class, $result[2]);
        $this->assertSame(3, $result[2]->getId());
        $this->assertSame('Bob', $result[2]->getName());
    }

    public function testFromArrayCollectionThrowsOnInvalidItem(): void
    {
        /** @var array<int, array<string, mixed>> $data */
        $data = [
            ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false],
            'invalid', // Not an array - will cause TypeError
            ['id' => 3, 'name' => 'Bob', 'active' => true, 'user_age' => 35, 'is_admin' => false],
        ];

        $this->expectException(\TypeError::class);

        $this->mapper->fromArrayCollection($data, TestClass::class);
    }

    public function testFromArrayCollectionWithStrictMode(): void
    {
        $mapper = new Mapper(MapperOptions::withStrictMode());

        $data = [
            ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false],
            ['id' => 2, 'name' => 'Jane', 'active' => false, 'user_age' => 25, 'is_admin' => true, 'unknown' => 'error'],
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Unknown key 'unknown'");

        $mapper->fromArrayCollection($data, TestClass::class);
    }

    public function testFromJsonCollectionWithEmptyArray(): void
    {
        $json = '[]';

        $result = $this->mapper->fromJsonCollection($json, TestClass::class);

        $this->assertCount(0, $result);
    }

    public function testFromJsonCollectionWithMultipleItems(): void
    {
        $json = '[
            {"id": 1, "name": "John", "active": true, "user_age": 30, "is_admin": false},
            {"id": 2, "name": "Jane", "active": false, "user_age": 25, "is_admin": true}
        ]';

        $result = $this->mapper->fromJsonCollection($json, TestClass::class);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(TestClass::class, $result[0]);
        $this->assertSame(1, $result[0]->getId());
        $this->assertInstanceOf(TestClass::class, $result[1]);
        $this->assertSame(2, $result[1]->getId());
    }

    public function testFromJsonCollectionThrowsOnInvalidJson(): void
    {
        $json = 'invalid json';

        $this->expectException(\JsonException::class);

        $this->mapper->fromJsonCollection($json, TestClass::class);
    }

    public function testFromJsonCollectionThrowsOnNonArrayJson(): void
    {
        $json = '"just a string"'; // String, not array

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON must decode to an array');

        $this->mapper->fromJsonCollection($json, TestClass::class);
    }

    public function testToArrayCollectionWithEmptyArray(): void
    {
        $result = $this->mapper->toArrayCollection([]);

        $this->assertCount(0, $result);
    }

    public function testToArrayCollectionWithSingleItem(): void
    {
        $obj = new UserWithConstructor(1, 'John');

        $result = $this->mapper->toArrayCollection([$obj]);

        $this->assertCount(1, $result);
        $this->assertSame(['id' => 1, 'name' => 'John', 'active' => true], $result[0]);
    }

    public function testToArrayCollectionWithMultipleItems(): void
    {
        $objects = [
            new UserWithConstructor(1, 'John'),
            new UserWithConstructor(2, 'Jane'),
            new UserWithConstructor(3, 'Bob'),
        ];

        $result = $this->mapper->toArrayCollection($objects);

        $this->assertCount(3, $result);
        $this->assertSame(['id' => 1, 'name' => 'John', 'active' => true], $result[0]);
        $this->assertSame(['id' => 2, 'name' => 'Jane', 'active' => true], $result[1]);
        $this->assertSame(['id' => 3, 'name' => 'Bob', 'active' => true], $result[2]);
    }

    public function testToArrayCollectionThrowsOnInvalidItem(): void
    {
        /** @var array<int, object> $collection */
        $collection = [
            new UserWithConstructor(1, 'John'),
            'invalid', // Not an object - will cause TypeError
        ];

        $this->expectException(\TypeError::class);

        $this->mapper->toArrayCollection($collection);
    }

    public function testToJsonCollectionWithEmptyArray(): void
    {
        $result = $this->mapper->toJsonCollection([]);

        $this->assertSame('[]', $result);
    }

    public function testToJsonCollectionWithMultipleItems(): void
    {
        $objects = [
            new UserWithConstructor(1, 'John'),
            new UserWithConstructor(2, 'Jane'),
        ];

        $result = $this->mapper->toJsonCollection($objects);

        $expected = '[{"id":1,"name":"John","active":true},{"id":2,"name":"Jane","active":true}]';
        $this->assertSame($expected, $result);
    }

    public function testRoundTripWithCollection(): void
    {
        // Original data
        $originalData = [
            ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false],
            ['id' => 2, 'name' => 'Jane', 'active' => false, 'user_age' => 25, 'is_admin' => true],
        ];

        // Array -> Objects
        $objects = $this->mapper->fromArrayCollection($originalData, TestClass::class);

        // Objects -> Array
        $resultData = $this->mapper->toArrayCollection($objects);

        // Verify structure is preserved
        $this->assertCount(2, $resultData);
        $this->assertArrayHasKey('id', $resultData[0]);
        $this->assertArrayHasKey('name', $resultData[0]);
        $this->assertSame(1, $resultData[0]['id']);
        $this->assertSame('John', $resultData[0]['name']);
    }

    public function testRoundTripWithJsonCollection(): void
    {
        // Original JSON (with active since it has default value)
        $originalJson = '[{"id":1,"name":"John","active":true},{"id":2,"name":"Jane","active":true}]';

        // JSON -> Objects
        $objects = $this->mapper->fromJsonCollection($originalJson, UserWithConstructor::class);

        // Objects -> JSON
        $resultJson = $this->mapper->toJsonCollection($objects);

        // Verify JSON is preserved
        $this->assertSame($originalJson, $resultJson);
    }

    public function testBatchProcessingMaintainsOrder(): void
    {
        $data = [
            ['id' => 5, 'name' => 'Five', 'active' => true, 'user_age' => 30, 'is_admin' => false],
            ['id' => 3, 'name' => 'Three', 'active' => true, 'user_age' => 30, 'is_admin' => false],
            ['id' => 1, 'name' => 'One', 'active' => true, 'user_age' => 30, 'is_admin' => false],
            ['id' => 4, 'name' => 'Four', 'active' => true, 'user_age' => 30, 'is_admin' => false],
            ['id' => 2, 'name' => 'Two', 'active' => true, 'user_age' => 30, 'is_admin' => false],
        ];

        $objects = $this->mapper->fromArrayCollection($data, TestClass::class);

        // Verify order is maintained
        $this->assertSame(5, $objects[0]->getId());
        $this->assertSame(3, $objects[1]->getId());
        $this->assertSame(1, $objects[2]->getId());
        $this->assertSame(4, $objects[3]->getId());
        $this->assertSame(2, $objects[4]->getId());
    }
}
