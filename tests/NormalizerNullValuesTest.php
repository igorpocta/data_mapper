<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\MapperOptions;

class NullValueInAddress
{
    public function __construct(
        public readonly ?string $street = null,
        public readonly ?string $city = null,
    ) {
    }
}

class NullValueInPerson
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email = null,
        public readonly ?NullValueInAddress $address = null,
    ) {
    }
}

class NormalizerNullValuesTest extends TestCase
{
    public function testNullPropertiesIncludedByDefault(): void
    {
        $mapper = new Mapper();

        $person = new NullValueInPerson(name: 'John', email: null);
        $data = $mapper->toArray($person);

        $this->assertSame('John', $data['name']);
        $this->assertArrayHasKey('email', $data);
        $this->assertNull($data['email']);
    }

    public function testNullPropertiesSkippedWhenOptionSet(): void
    {
        $mapper = new Mapper(new MapperOptions(skipNullValues: true));

        $person = new NullValueInPerson(name: 'John', email: null);
        $data = $mapper->toArray($person);

        $this->assertSame('John', $data['name']);
        $this->assertArrayNotHasKey('email', $data);
    }

    public function testNestedObjectWithNullPropertiesIncluded(): void
    {
        $mapper = new Mapper();

        $person = new NullValueInPerson(
            name: 'John',
            address: new NullValueInAddress(street: null, city: null),
        );
        $data = $mapper->toArray($person);

        $this->assertArrayHasKey('address', $data);
        $address = $data['address'];
        $this->assertIsArray($address);
        /** @var array<string, mixed> $address */
        $this->assertArrayHasKey('street', $address);
        $this->assertNull($address['street']);
        $this->assertArrayHasKey('city', $address);
        $this->assertNull($address['city']);
    }

    public function testNestedObjectNullPropertiesInJson(): void
    {
        $mapper = new Mapper();

        $person = new NullValueInPerson(
            name: 'John',
            address: new NullValueInAddress(street: null, city: null),
        );
        $json = $mapper->toJson($person);
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        /** @var array<string, mixed> $decoded */
        $this->assertArrayHasKey('address', $decoded);
        $address = $decoded['address'];
        $this->assertIsArray($address);
        /** @var array<string, mixed> $address */
        $this->assertArrayHasKey('street', $address);
        $this->assertNull($address['street']);
        $this->assertArrayHasKey('city', $address);
        $this->assertNull($address['city']);
    }

    public function testNullNestedObjectItselfIsNull(): void
    {
        $mapper = new Mapper();

        $person = new NullValueInPerson(name: 'John', address: null);
        $data = $mapper->toArray($person);

        $this->assertArrayHasKey('address', $data);
        $this->assertNull($data['address']);
    }

    public function testSkipNullValuesAlsoSkipsNestedNullProperties(): void
    {
        $mapper = new Mapper(new MapperOptions(skipNullValues: true));

        $person = new NullValueInPerson(
            name: 'John',
            address: new NullValueInAddress(street: '123 Main', city: null),
        );
        $data = $mapper->toArray($person);

        $this->assertArrayHasKey('address', $data);
        $address = $data['address'];
        $this->assertIsArray($address);
        /** @var array<string, mixed> $address */
        $this->assertSame('123 Main', $address['street']);
        $this->assertArrayNotHasKey('city', $address);
    }
}
