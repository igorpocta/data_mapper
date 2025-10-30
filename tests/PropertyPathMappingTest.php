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

    public function testMapPropertyPathGivesDetailedErrorWhenMissingKey(): void
    {
        $data = [
            'user' => [
                'profile' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'age' => 30
                ]
            ]
        ];

        try {
            $this->mapper->fromArray($data, UserWithMissingPath::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (\Pocta\DataMapper\Exceptions\ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('user.profile.email', $errors);
            $message = $errors['user.profile.email'];

            // Check that error contains context about where it failed and available keys
            $this->assertStringContainsString("Missing required property 'email'", $message);
            $this->assertStringContainsString('path resolution failed', $message);
            $this->assertStringContainsString('available keys:', $message);
            $this->assertStringContainsString('firstName', $message);
        }
    }

    public function testMapPropertyPathGivesDetailedErrorWhenArrayIndexOutOfBounds(): void
    {
        $data = [
            'user' => [
                'addresses' => [
                    ['street' => 'First Street']
                    // Only one address, but we try to access index 2
                ]
            ]
        ];

        try {
            $this->mapper->fromArray($data, UserWithOutOfBoundsIndex::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (\Pocta\DataMapper\Exceptions\ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('user.addresses[2].street', $errors);
            $message = $errors['user.addresses[2].street'];

            // Check that error mentions array bounds
            $this->assertStringContainsString("Missing required property 'street'", $message);
            $this->assertStringContainsString('path resolution failed', $message);
            $this->assertStringContainsString('array has 1 elements', $message);
        }
    }

    public function testMapPropertyPathGivesDetailedErrorWhenNestedPathMissing(): void
    {
        $data = [
            'company' => [
                'name' => 'ACME Corp'
                // 'departments' key is missing entirely
            ]
        ];

        try {
            $this->mapper->fromArray($data, CompanyWithMissingDepartment::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (\Pocta\DataMapper\Exceptions\ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('company.departments[0].name', $errors);
            $message = $errors['company.departments[0].name'];

            // Check that error shows where path resolution failed
            $this->assertStringContainsString("Missing required property 'departmentName'", $message);
            $this->assertStringContainsString('path resolution failed at', $message);
            $this->assertStringContainsString('available keys:', $message);
            $this->assertStringContainsString('name', $message);
        }
    }

    public function testMapPropertyPathInvalidSyntaxGivesError(): void
    {
        $data = [
            'user' => ['name' => 'John']
        ];

        try {
            $this->mapper->fromArray($data, UserWithInvalidPathSyntax::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (\Pocta\DataMapper\Exceptions\ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertNotEmpty($errors);

            // Should contain error about invalid syntax
            $firstError = reset($errors);
            $this->assertStringContainsString('Invalid property path syntax', $firstError);
        }
    }
}

class UserWithMissingPath
{
    public function __construct(
        #[MapProperty(path: 'user.profile.email')]
        public string $email
    ) {
    }
}

class UserWithOutOfBoundsIndex
{
    public function __construct(
        #[MapProperty(path: 'user.addresses[2].street')]
        public string $street
    ) {
    }
}

class CompanyWithMissingDepartment
{
    public function __construct(
        #[MapProperty(path: 'company.departments[0].name')]
        public string $departmentName
    ) {
    }
}

class UserWithInvalidPathSyntax
{
    public function __construct(
        #[MapProperty(path: 'user.addresses[abc].street')]
        public string $street
    ) {
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
