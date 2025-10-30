<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\MapperOptions;
use Pocta\DataMapper\Exceptions\ValidationException;
use Tests\Fixtures\TestClass;
use Tests\Fixtures\UserWithConstructor;

class MergeTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testMergeUpdatesOnlySpecifiedProperties(): void
    {
        // Create object with initial values
        $data = ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false];
        $object = $this->mapper->fromArray($data, TestClass::class);

        // Merge partial update - only change name
        $partialData = ['name' => 'Jane'];
        $this->mapper->merge($partialData, $object);

        // Check that only name was updated
        $this->assertSame('Jane', $object->getName());
        // Other properties remain unchanged
        $this->assertSame(1, $object->getId());
        $this->assertTrue($object->isActive());
        $this->assertSame(30, $object->getAge());
        $this->assertFalse($object->isAdmin());
    }

    public function testMergeUpdatesMultipleProperties(): void
    {
        $data = ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false];
        $object = $this->mapper->fromArray($data, TestClass::class);

        // Update multiple properties
        $partialData = [
            'name' => 'Jane',
            'user_age' => 35,
            'is_admin' => true
        ];
        $this->mapper->merge($partialData, $object);

        $this->assertSame('Jane', $object->getName());
        $this->assertSame(35, $object->getAge());
        $this->assertTrue($object->isAdmin());
        // Unchanged
        $this->assertSame(1, $object->getId());
        $this->assertTrue($object->isActive());
    }

    public function testMergeReturnsTheSameInstance(): void
    {
        $data = ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false];
        $object = $this->mapper->fromArray($data, TestClass::class);

        $partialData = ['name' => 'Jane'];
        $result = $this->mapper->merge($partialData, $object);

        $this->assertSame($object, $result);
    }

    public function testMergeWithSkipNullEnabled(): void
    {
        $user = new UserWithConstructor(1, 'John', true);
        $user->setEmail('john@example.com');

        // With skipNull=true, null values are ignored
        $partialData = ['email' => null, 'name' => 'Jane'];
        $this->mapper->merge($partialData, $user, skipNull: true);

        // Email should remain unchanged (null was skipped)
        $this->assertSame('john@example.com', $user->getEmail());
        // Name should be updated
        $this->assertSame('Jane', $user->getName());
    }

    public function testMergeWithSkipNullDisabled(): void
    {
        $user = new UserWithConstructor(1, 'John', true);
        $user->setEmail('john@example.com');

        // With skipNull=false (default), updating with non-null works
        $partialData = ['email' => 'jane@example.com'];
        $this->mapper->merge($partialData, $user, skipNull: false);

        $this->assertSame('jane@example.com', $user->getEmail());
    }

    public function testMergeWithEmptyData(): void
    {
        $data = ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false];
        $object = $this->mapper->fromArray($data, TestClass::class);

        // Merge empty data - nothing should change
        $this->mapper->merge([], $object);

        $this->assertSame(1, $object->getId());
        $this->assertSame('John', $object->getName());
        $this->assertTrue($object->isActive());
    }

    public function testMergeIgnoresUnknownPropertiesInNonStrictMode(): void
    {
        $data = ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false];
        $object = $this->mapper->fromArray($data, TestClass::class);

        // Unknown properties should be ignored
        $partialData = [
            'name' => 'Jane',
            'unknown_field' => 'value'
        ];
        $this->mapper->merge($partialData, $object);

        // Known property is updated
        $this->assertSame('Jane', $object->getName());
        // No exception thrown for unknown field
    }

    public function testMergeThrowsOnUnknownPropertyInStrictMode(): void
    {
        $mapper = new Mapper(MapperOptions::withStrictMode());

        $data = ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false];
        $object = $mapper->fromArray($data, TestClass::class);

        $partialData = [
            'name' => 'Jane',
            'unknown_field' => 'value'
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Unknown key 'unknown_field'");

        $mapper->merge($partialData, $object);
    }

    public function testMergeWithCustomPropertyNames(): void
    {
        $data = ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false];
        $object = $this->mapper->fromArray($data, TestClass::class);

        // Use JSON key name (user_age instead of age)
        $partialData = ['user_age' => 40];
        $this->mapper->merge($partialData, $object);

        $this->assertSame(40, $object->getAge());
    }

    public function testMergeWithTypeConversion(): void
    {
        $data = ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false];
        $object = $this->mapper->fromArray($data, TestClass::class);

        // Pass string that should be converted to int
        $partialData = ['id' => '999'];
        $this->mapper->merge($partialData, $object);

        $this->assertSame(999, $object->getId());
        $this->assertIsInt($object->getId());
    }

    public function testMergeWithBooleanConversion(): void
    {
        $data = ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false];
        $object = $this->mapper->fromArray($data, TestClass::class);

        // Various boolean representations
        $partialData = ['active' => 0, 'is_admin' => 'true'];
        $this->mapper->merge($partialData, $object);

        $this->assertFalse($object->isActive());
        $this->assertTrue($object->isAdmin());
    }

    public function testMergePreservesUnchangedPropertiesWithNullables(): void
    {
        $user = new UserWithConstructor(1, 'John', true);
        $user->setEmail('john@example.com');

        // Update only name, email should remain
        $partialData = ['name' => 'Jane'];
        $this->mapper->merge($partialData, $user);

        $this->assertSame('Jane', $user->getName());
        $this->assertSame('john@example.com', $user->getEmail());
        $this->assertTrue($user->isActive());
    }

    public function testMergeMultipleTimesOnSameObject(): void
    {
        $data = ['id' => 1, 'name' => 'John', 'active' => true, 'user_age' => 30, 'is_admin' => false];
        $object = $this->mapper->fromArray($data, TestClass::class);

        // First merge
        $this->mapper->merge(['name' => 'Jane'], $object);
        $this->assertSame('Jane', $object->getName());

        // Second merge
        $this->mapper->merge(['user_age' => 35], $object);
        $this->assertSame(35, $object->getAge());

        // Third merge
        $this->mapper->merge(['is_admin' => true], $object);
        $this->assertTrue($object->isAdmin());

        // All values should be accumulated
        $this->assertSame('Jane', $object->getName());
        $this->assertSame(35, $object->getAge());
        $this->assertTrue($object->isAdmin());
    }
}
