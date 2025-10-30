<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\MapProperty;

class PropertyPathMappingTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testMapPropertyWithNestedPath(): void
    {
        $data = [
            'user' => [
                'permanentAddress' => [
                    'streetName' => 'Main Street',
                    'city' => 'Prague'
                ]
            ]
        ];

        $object = $this->mapper->fromArray($data, UserWithNestedAddress::class);

        $this->assertSame('Main Street', $object->street);
        $this->assertSame('Prague', $object->city);
    }

    public function testMapPropertyWithArrayIndex(): void
    {
        $data = [
            'user' => [
                'addresses' => [
                    [
                        'streetName' => 'First Street',
                        'city' => 'Prague'
                    ],
                    [
                        'streetName' => 'Second Street',
                        'city' => 'Brno'
                    ]
                ]
            ]
        ];

        $object = $this->mapper->fromArray($data, UserWithIndexedAddress::class);

        $this->assertSame('First Street', $object->firstAddressStreet);
        $this->assertSame('Second Street', $object->secondAddressStreet);
    }

    public function testMapPropertyWithMixedPath(): void
    {
        $data = [
            'company' => [
                'departments' => [
                    [
                        'name' => 'IT',
                        'manager' => [
                            'name' => 'John Doe',
                            'email' => 'john@example.com'
                        ]
                    ]
                ]
            ]
        ];

        $object = $this->mapper->fromArray($data, CompanyInfo::class);

        $this->assertSame('IT', $object->departmentName);
        $this->assertSame('John Doe', $object->managerName);
    }

    public function testMapPropertyWithPathReturnsNullForMissingData(): void
    {
        $data = [
            'user' => [
                'name' => 'John'
            ]
        ];

        $object = $this->mapper->fromArray($data, UserWithNullableAddress::class);

        $this->assertNull($object->street);
    }

    public function testMapPropertyWithPathAndConstructor(): void
    {
        $data = [
            'profile' => [
                'personal' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe'
                ],
                'contact' => [
                    'email' => 'john@example.com'
                ]
            ]
        ];

        $object = $this->mapper->fromArray($data, UserProfile::class);

        $this->assertSame('John', $object->firstName);
        $this->assertSame('Doe', $object->lastName);
        $this->assertSame('john@example.com', $object->email);
    }

    public function testMapPropertyPathCannotBeUsedWithName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot specify both $name and $path parameters');

        new MapProperty(name: 'street', path: 'user.address.street');
    }
}

class UserWithNestedAddress
{
    public function __construct(
        #[MapProperty(path: 'user.permanentAddress.streetName')]
        public string $street,
        #[MapProperty(path: 'user.permanentAddress.city')]
        public string $city
    ) {
    }
}

class UserWithIndexedAddress
{
    public function __construct(
        #[MapProperty(path: 'user.addresses[0].streetName')]
        public string $firstAddressStreet,
        #[MapProperty(path: 'user.addresses[1].streetName')]
        public string $secondAddressStreet
    ) {
    }
}

class CompanyInfo
{
    public function __construct(
        #[MapProperty(path: 'company.departments[0].name')]
        public string $departmentName,
        #[MapProperty(path: 'company.departments[0].manager.name')]
        public string $managerName
    ) {
    }
}

class UserWithNullableAddress
{
    public function __construct(
        #[MapProperty(path: 'user.address.streetName')]
        public ?string $street = null
    ) {
    }
}

class UserProfile
{
    public function __construct(
        #[MapProperty(path: 'profile.personal.firstName')]
        public string $firstName,
        #[MapProperty(path: 'profile.personal.lastName')]
        public string $lastName,
        #[MapProperty(path: 'profile.contact.email')]
        public string $email
    ) {
    }
}
