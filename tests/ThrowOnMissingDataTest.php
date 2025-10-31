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
