<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\PropertyPathResolver;
use InvalidArgumentException;

class PropertyPathResolverTest extends TestCase
{
    public function testResolveSimpleProperty(): void
    {
        $data = ['name' => 'John'];
        $result = PropertyPathResolver::resolve($data, 'name');
        $this->assertSame('John', $result);
    }

    public function testResolveNestedProperty(): void
    {
        $data = [
            'user' => [
                'address' => [
                    'street' => 'Main Street'
                ]
            ]
        ];
        $result = PropertyPathResolver::resolve($data, 'user.address.street');
        $this->assertSame('Main Street', $result);
    }

    public function testResolveArrayIndex(): void
    {
        $data = [
            'addresses' => [
                ['street' => 'First Street'],
                ['street' => 'Second Street']
            ]
        ];
        $result = PropertyPathResolver::resolve($data, 'addresses[0].street');
        $this->assertSame('First Street', $result);
    }

    public function testResolveArrayIndexSecondElement(): void
    {
        $data = [
            'addresses' => [
                ['street' => 'First Street'],
                ['street' => 'Second Street']
            ]
        ];
        $result = PropertyPathResolver::resolve($data, 'addresses[1].street');
        $this->assertSame('Second Street', $result);
    }

    public function testResolveMixedNotation(): void
    {
        $data = [
            'user' => [
                'addresses' => [
                    [
                        'streetName' => 'Main Street',
                        'city' => 'Prague'
                    ],
                    [
                        'streetName' => 'Second Street',
                        'city' => 'Brno'
                    ]
                ]
            ]
        ];
        $result = PropertyPathResolver::resolve($data, 'user.addresses[0].streetName');
        $this->assertSame('Main Street', $result);

        $result = PropertyPathResolver::resolve($data, 'user.addresses[1].city');
        $this->assertSame('Brno', $result);
    }

    public function testResolveNonExistentProperty(): void
    {
        $data = ['name' => 'John'];
        $result = PropertyPathResolver::resolve($data, 'age');
        $this->assertNull($result);
    }

    public function testResolveNonExistentNestedProperty(): void
    {
        $data = ['user' => ['name' => 'John']];
        $result = PropertyPathResolver::resolve($data, 'user.address.street');
        $this->assertNull($result);
    }

    public function testResolveNonExistentArrayIndex(): void
    {
        $data = ['addresses' => [['street' => 'First Street']]];
        $result = PropertyPathResolver::resolve($data, 'addresses[5].street');
        $this->assertNull($result);
    }

    public function testExistsSimpleProperty(): void
    {
        $data = ['name' => 'John'];
        $this->assertTrue(PropertyPathResolver::exists($data, 'name'));
        $this->assertFalse(PropertyPathResolver::exists($data, 'age'));
    }

    public function testExistsNestedProperty(): void
    {
        $data = [
            'user' => [
                'address' => [
                    'street' => 'Main Street'
                ]
            ]
        ];
        $this->assertTrue(PropertyPathResolver::exists($data, 'user.address.street'));
        $this->assertFalse(PropertyPathResolver::exists($data, 'user.address.city'));
    }

    public function testExistsArrayIndex(): void
    {
        $data = [
            'addresses' => [
                ['street' => 'First Street'],
                ['street' => 'Second Street']
            ]
        ];
        $this->assertTrue(PropertyPathResolver::exists($data, 'addresses[0].street'));
        $this->assertTrue(PropertyPathResolver::exists($data, 'addresses[1].street'));
        $this->assertFalse(PropertyPathResolver::exists($data, 'addresses[2].street'));
    }

    public function testIsValidPath(): void
    {
        $this->assertTrue(PropertyPathResolver::isValidPath('name'));
        $this->assertTrue(PropertyPathResolver::isValidPath('user.address.street'));
        $this->assertTrue(PropertyPathResolver::isValidPath('addresses[0].street'));
        $this->assertTrue(PropertyPathResolver::isValidPath('user.addresses[0].streetName'));
        $this->assertFalse(PropertyPathResolver::isValidPath(''));
    }

    public function testInvalidPathMissingClosingBracket(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("missing closing bracket");
        PropertyPathResolver::resolve(['addresses' => []], 'addresses[0');
    }

    public function testInvalidPathNonNumericIndex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("array index must be numeric");
        PropertyPathResolver::resolve(['addresses' => []], 'addresses[abc]');
    }

    public function testResolveEmptyPath(): void
    {
        $data = ['name' => 'John'];
        $result = PropertyPathResolver::resolve($data, '');
        $this->assertNull($result);
    }

    public function testResolveComplexNestedStructure(): void
    {
        $data = [
            'company' => [
                'departments' => [
                    [
                        'name' => 'IT',
                        'employees' => [
                            ['name' => 'John', 'position' => 'Developer'],
                            ['name' => 'Jane', 'position' => 'Manager']
                        ]
                    ],
                    [
                        'name' => 'HR',
                        'employees' => [
                            ['name' => 'Bob', 'position' => 'Recruiter']
                        ]
                    ]
                ]
            ]
        ];

        $result = PropertyPathResolver::resolve($data, 'company.departments[0].employees[1].name');
        $this->assertSame('Jane', $result);

        $result = PropertyPathResolver::resolve($data, 'company.departments[1].employees[0].position');
        $this->assertSame('Recruiter', $result);
    }

    public function testResolveWithNullValues(): void
    {
        $data = [
            'user' => [
                'name' => null,
                'address' => null
            ]
        ];

        $result = PropertyPathResolver::resolve($data, 'user.name');
        $this->assertNull($result);

        $result = PropertyPathResolver::resolve($data, 'user.address');
        $this->assertNull($result);

        // This should return null because address is null, not because street doesn't exist
        $result = PropertyPathResolver::resolve($data, 'user.address.street');
        $this->assertNull($result);
    }

    public function testExistsWithNullValues(): void
    {
        $data = [
            'user' => [
                'name' => null,
                'address' => null
            ]
        ];

        // Key exists even if value is null
        $this->assertTrue(PropertyPathResolver::exists($data, 'user.name'));
        $this->assertTrue(PropertyPathResolver::exists($data, 'user.address'));

        // This should return false because address is null, can't check nested property
        $this->assertFalse(PropertyPathResolver::exists($data, 'user.address.street'));
    }
}
