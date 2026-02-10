<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\MapperOptions;
use Pocta\DataMapper\Exceptions\ValidationException;

class ThrowOnMissingDataTest extends TestCase
{
    public function testThrowsOnMissingDataByDefault(): void
    {
        $mapper = new Mapper();

        $data = [
            'email' => 'test@example.com',
            // Missing 'name' which is required
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing required parameter');

        $mapper->fromArray($data, ThrowOnMissingDataUser::class);
    }

    public function testDoesNotThrowWhenDisabled(): void
    {
        $options = new MapperOptions(throwOnMissingData: false);
        $mapper = new Mapper($options);

        $data = [
            'email' => 'test@example.com',
            // Missing 'name' which has default value
        ];

        $user = $mapper->fromArray($data, ThrowOnMissingDataUserWithDefault::class);

        $this->assertInstanceOf(ThrowOnMissingDataUserWithDefault::class, $user);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('Unknown', $user->name); // Uses default value
    }

    public function testCanToggleThrowOnMissingData(): void
    {
        $mapper = new Mapper();

        // Initially enabled (default)
        $this->assertTrue($mapper->isThrowOnMissingData());

        // Disable it
        $mapper->setThrowOnMissingData(false);
        $this->assertFalse($mapper->isThrowOnMissingData());

        $data = [
            'email' => 'test@example.com',
        ];

        $user = $mapper->fromArray($data, ThrowOnMissingDataUserWithDefault::class);
        $this->assertInstanceOf(ThrowOnMissingDataUserWithDefault::class, $user);
        $this->assertEquals('Unknown', $user->name);

        // Re-enable it
        $mapper->setThrowOnMissingData(true);
        $this->assertTrue($mapper->isThrowOnMissingData());

        $this->expectException(ValidationException::class);
        $mapper->fromArray($data, ThrowOnMissingDataUser::class);
    }

    public function testThrowsForMissingDiscriminatorWhenEnabled(): void
    {
        $mapper = new Mapper();

        $data = [
            'id' => 1,
            // Missing 'type' discriminator
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Missing discriminator property');

        $mapper->fromArray($data, ThrowOnMissingDataBaseShape::class);
    }

    public function testDoesNotThrowForMissingDiscriminatorWhenDisabled(): void
    {
        $options = new MapperOptions(throwOnMissingData: false);
        $mapper = new Mapper($options);

        $data = [
            'id' => 1,
            // Missing 'type' discriminator - will fallback to base class
        ];

        $shape = $mapper->fromArray($data, ThrowOnMissingDataBaseShape::class);
        $this->assertInstanceOf(ThrowOnMissingDataBaseShape::class, $shape);
    }

    public function testNullablePropertiesWorkRegardlessOfSetting(): void
    {
        $options = new MapperOptions(throwOnMissingData: true);
        $mapper = new Mapper($options);

        $data = [
            'name' => 'John',
            'email' => 'john@example.com',
            // Missing 'age' which is nullable
        ];

        $user = $mapper->fromArray($data, ThrowOnMissingDataUserWithNullable::class);
        $this->assertInstanceOf(ThrowOnMissingDataUserWithNullable::class, $user);
        $this->assertNull($user->age);
    }

    public function testThrowsOnMissingRequiredPropertyWithoutMapPropertyAttribute(): void
    {
        $mapper = new Mapper();

        $data = [
            'name' => 'Test',
            // Missing 'hspId' which is required (int, no default, no MapProperty)
        ];

        $this->expectException(ValidationException::class);

        $mapper->fromArray($data, ThrowOnMissingDataNonConstructorRequired::class);
    }

    public function testDoesNotThrowOnMissingPropertyWithDefaultValue(): void
    {
        $mapper = new Mapper();

        $data = [
            'name' => 'Test',
            'hspId' => 42,
            // Missing 'label' which has default value
        ];

        $result = $mapper->fromArray($data, ThrowOnMissingDataNonConstructorRequired::class);
        $this->assertSame(42, $result->hspId);
        $this->assertSame('default', $result->label);
    }

    public function testDoesNotThrowOnMissingNullablePropertyWithoutConstructor(): void
    {
        $mapper = new Mapper();

        $data = [
            'name' => 'Test',
            'hspId' => 42,
            // Missing 'description' which is nullable
        ];

        $result = $mapper->fromArray($data, ThrowOnMissingDataNonConstructorRequired::class);
        $this->assertSame(42, $result->hspId);
    }

    public function testCollectsMultipleMissingPropertyErrors(): void
    {
        $mapper = new Mapper();

        $data = [
            // Missing both 'status' and 'priority' which are required non-constructor properties
        ];

        try {
            $mapper->fromArray($data, ThrowOnMissingDataMultipleRequired::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('status', $errors);
            $this->assertArrayHasKey('priority', $errors);
        }
    }

    public function testDoesNotThrowOnMissingPropertyWhenThrowOnMissingDataDisabled(): void
    {
        $options = new MapperOptions(throwOnMissingData: false);
        $mapper = new Mapper($options);

        $data = [
            'name' => 'Test',
            // Missing 'hspId' - but throwOnMissingData is disabled
        ];

        $result = $mapper->fromArray($data, ThrowOnMissingDataNonConstructorRequired::class);
        $this->assertInstanceOf(ThrowOnMissingDataNonConstructorRequired::class, $result);
    }

    // --- Enum property without constructor, without MapProperty ---

    public function testThrowsOnMissingRequiredEnumPropertyWithoutMapProperty(): void
    {
        $mapper = new Mapper();

        $data = [
            'name' => 'Test',
            // Missing 'purpose' which is a required BackedEnum property
        ];

        try {
            $mapper->fromArray($data, ThrowOnMissingDataWithEnum::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('purpose', $errors);
            $this->assertStringContainsString("Missing required property 'purpose'", $errors['purpose']);
        }
    }

    public function testSucceedsWhenEnumPropertyIsProvided(): void
    {
        $mapper = new Mapper();

        $data = [
            'name' => 'Test',
            'purpose' => 'external',
        ];

        $result = $mapper->fromArray($data, ThrowOnMissingDataWithEnum::class);
        $this->assertSame('Test', $result->name);
        $this->assertSame(ThrowOnMissingDataPurpose::External, $result->purpose);
    }

    // --- Inherited properties from parent class ---

    public function testThrowsOnMissingRequiredInheritedProperty(): void
    {
        $mapper = new Mapper();

        $data = [
            'hspId' => 42,
            // Missing 'name' from parent class
        ];

        $this->expectException(ValidationException::class);

        $mapper->fromArray($data, ThrowOnMissingDataChildRequest::class);
    }

    public function testThrowsOnMissingRequiredChildProperty(): void
    {
        $mapper = new Mapper();

        $data = [
            'name' => 'Test',
            // Missing 'hspId' from child class
        ];

        try {
            $mapper->fromArray($data, ThrowOnMissingDataChildRequest::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('hspId', $errors);
        }
    }

    public function testSucceedsWhenAllInheritedAndChildPropertiesProvided(): void
    {
        $mapper = new Mapper();

        $data = [
            'name' => 'Test',
            'hspId' => 42,
            'purpose' => 'internal',
        ];

        $result = $mapper->fromArray($data, ThrowOnMissingDataChildRequest::class);
        $this->assertSame('Test', $result->name);
        $this->assertSame(42, $result->hspId);
        $this->assertSame(ThrowOnMissingDataPurpose::Internal, $result->purpose);
    }

    public function testInheritedNullablePropertyDoesNotThrow(): void
    {
        $mapper = new Mapper();

        $data = [
            'name' => 'Test',
            'hspId' => 42,
            'purpose' => 'external',
            // Missing 'description' from parent - nullable, should not throw
        ];

        $result = $mapper->fromArray($data, ThrowOnMissingDataChildRequest::class);
        $this->assertSame(42, $result->hspId);
    }

    // --- Mix constructor + non-constructor: constructor errors throw first ---

    public function testConstructorErrorThrowsBeforePropertyErrors(): void
    {
        $mapper = new Mapper();

        $data = [
            // Missing 'name' (constructor) AND 'hspId' (property)
        ];

        try {
            $mapper->fromArray($data, ThrowOnMissingDataNonConstructorRequired::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            // Constructor error is thrown first, before property validation runs
            $this->assertArrayHasKey('name', $errors);
            // Property error is NOT collected because constructor fails first
            $this->assertArrayNotHasKey('hspId', $errors);
        }
    }

    // --- Property without constructor at all (no constructor class) ---

    public function testThrowsOnMissingPropertyInClassWithoutConstructor(): void
    {
        $mapper = new Mapper();

        $data = [
            // Missing all required properties
        ];

        try {
            $mapper->fromArray($data, ThrowOnMissingDataNoConstructor::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('id', $errors);
            $this->assertArrayHasKey('title', $errors);
            // Optional properties should NOT be in errors
            $this->assertArrayNotHasKey('note', $errors);
            $this->assertArrayNotHasKey('tag', $errors);
        }
    }

    public function testErrorMessageContainsPropertyPath(): void
    {
        $mapper = new Mapper();

        $data = ['name' => 'Test'];

        try {
            $mapper->fromArray($data, ThrowOnMissingDataNonConstructorRequired::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('hspId', $errors);
            $this->assertStringContainsString('hspId', $errors['hspId']);
            $this->assertStringContainsString("Missing required property", $errors['hspId']);
        }
    }
}

class ThrowOnMissingDataUser
{
    public function __construct(
        public string $name,
        public string $email
    ) {
    }
}

class ThrowOnMissingDataUserWithDefault
{
    public function __construct(
        public string $email,
        public string $name = 'Unknown'
    ) {
    }
}

class ThrowOnMissingDataUserWithNullable
{
    public function __construct(
        public string $name,
        public string $email,
        public ?int $age = null
    ) {
    }
}

#[\Pocta\DataMapper\Attributes\DiscriminatorMap(
    property: 'type',
    mapping: [
        'circle' => ThrowOnMissingDataCircle::class,
        'square' => ThrowOnMissingDataSquare::class,
    ]
)]
class ThrowOnMissingDataBaseShape
{
    public function __construct(
        public int $id
    ) {
    }
}

class ThrowOnMissingDataCircle extends ThrowOnMissingDataBaseShape
{
    public function __construct(
        int $id,
        public float $radius
    ) {
        parent::__construct($id);
    }
}

class ThrowOnMissingDataSquare extends ThrowOnMissingDataBaseShape
{
    public function __construct(
        int $id,
        public float $side
    ) {
        parent::__construct($id);
    }
}

/**
 * DTO with mix of constructor and non-constructor properties.
 * Simulates real-world case like NewLeadTokenRequest where
 * non-constructor properties without #[MapProperty] are required.
 */
class ThrowOnMissingDataNonConstructorRequired
{
    public int $hspId; // required: no default, not nullable, no MapProperty

    public ?string $description = null; // optional: nullable with default

    public string $label = 'default'; // optional: has default value

    public function __construct(
        public string $name
    ) {
    }
}

class ThrowOnMissingDataMultipleRequired
{
    public string $status; // required

    public int $priority; // required
}

enum ThrowOnMissingDataPurpose: string
{
    case External = 'external';
    case Internal = 'internal';
}

/** DTO with enum property, no constructor, no MapProperty */
class ThrowOnMissingDataWithEnum
{
    public string $name;

    public ThrowOnMissingDataPurpose $purpose; // required enum, no default, no MapProperty
}

/** Parent class simulating NewTokenRequest */
class ThrowOnMissingDataParentRequest
{
    public string $name; // required in parent

    public ?string $description = null; // nullable in parent
}

/** Child class simulating NewLeadTokenRequest */
class ThrowOnMissingDataChildRequest extends ThrowOnMissingDataParentRequest
{
    public int $hspId; // required in child, no default, no MapProperty

    public ThrowOnMissingDataPurpose $purpose; // required enum in child
}

/** Class without constructor at all - all public properties */
class ThrowOnMissingDataNoConstructor
{
    public int $id; // required

    public string $title; // required

    public ?string $note = null; // optional: nullable with default

    public string $tag = 'general'; // optional: has default
}
