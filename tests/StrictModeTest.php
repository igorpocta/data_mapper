<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\MapperOptions;
use Pocta\DataMapper\Exceptions\ValidationException;
use Tests\Fixtures\TestClass;

class StrictModeTest extends TestCase
{
    public function testStrictModeDisabledByDefault(): void
    {
        $mapper = new Mapper();

        $this->assertFalse($mapper->isStrictMode());
    }

    public function testStrictModeCanBeEnabled(): void
    {
        $mapper = new Mapper(MapperOptions::withStrictMode());

        $this->assertTrue($mapper->isStrictMode());
    }

    public function testStrictModeCanBeSetDynamically(): void
    {
        $mapper = new Mapper();

        $this->assertFalse($mapper->isStrictMode());

        $mapper->setStrictMode(true);
        $this->assertTrue($mapper->isStrictMode());

        $mapper->setStrictMode(false);
        $this->assertFalse($mapper->isStrictMode());
    }

    public function testStrictModeAllowsValidKeys(): void
    {
        $mapper = new Mapper(MapperOptions::withStrictMode());

        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'active' => true,
            'user_age' => 30,
            'is_admin' => false
        ];

        $object = $mapper->fromArray($data, TestClass::class);

        $this->assertInstanceOf(TestClass::class, $object);
        $this->assertSame(1, $object->getId());
        $this->assertSame('John Doe', $object->getName());
        $this->assertTrue($object->isActive());
        $this->assertSame(30, $object->getAge());
        $this->assertFalse($object->isAdmin());
    }

    public function testStrictModeThrowsOnUnknownKey(): void
    {
        $mapper = new Mapper(MapperOptions::withStrictMode());

        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'active' => true,
            'user_age' => 30,
            'is_admin' => false,
            'unknown_field' => 'not allowed'
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Unknown key 'unknown_field'");

        $mapper->fromArray($data, TestClass::class);
    }

    public function testStrictModeThrowsOnMultipleUnknownKeys(): void
    {
        $mapper = new Mapper(MapperOptions::withStrictMode());

        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'active' => true,
            'user_age' => 30,
            'is_admin' => false,
            'unknown_field_1' => 'not allowed',
            'unknown_field_2' => 'also not allowed'
        ];

        try {
            $mapper->fromArray($data, TestClass::class);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertCount(2, $errors);
            $this->assertArrayHasKey('unknown_field_1', $errors);
            $this->assertArrayHasKey('unknown_field_2', $errors);
        }
    }

    public function testStrictModeDisabledIgnoresUnknownKeys(): void
    {
        $mapper = new Mapper();

        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'active' => true,
            'user_age' => 30,
            'is_admin' => false,
            'unknown_field' => 'ignored'
        ];

        $object = $mapper->fromArray($data, TestClass::class);

        $this->assertInstanceOf(TestClass::class, $object);
        $this->assertSame(1, $object->getId());
    }

    public function testStrictModeFromJson(): void
    {
        $mapper = new Mapper(MapperOptions::withStrictMode());

        $json = '{"id": 1, "name": "John Doe", "active": true, "user_age": 30, "is_admin": false, "unknown": "error"}';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Unknown key 'unknown'");

        $mapper->fromJson($json, TestClass::class);
    }
}
