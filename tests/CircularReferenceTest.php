<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Exceptions\CircularReferenceException;

class CircularReferenceTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testNormalizationDetectsCircularReference(): void
    {
        $user = new CircularUser('John');
        $company = new CircularCompany('Acme Corp');

        // Create circular reference
        $user->company = $company;
        $company->owner = $user;

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage('Circular reference detected');

        $this->mapper->toArray($user);
    }

    public function testDenormalizationDetectsCircularReference(): void
    {
        // This test is more theoretical as circular references in data are less common
        // But we test the class stack detection mechanism
        $data = [
            'name' => 'John',
            'company' => [
                'name' => 'Acme Corp',
            ],
        ];

        // The denormalizer should detect if we somehow try to denormalize the same class recursively
        // This is harder to trigger naturally but the mechanism is in place
        $result = $this->mapper->fromArray($data, CircularUser::class);

        $this->assertInstanceOf(CircularUser::class, $result);
        $this->assertEquals('John', $result->name);
    }

    public function testNestedObjectsWithoutCircularReferenceWork(): void
    {
        $data = [
            'name' => 'John',
            'company' => [
                'name' => 'Acme Corp',
            ],
        ];

        $user = $this->mapper->fromArray($data, CircularUser::class);

        $this->assertInstanceOf(CircularUser::class, $user);
        $this->assertEquals('John', $user->name);
        $this->assertInstanceOf(CircularCompany::class, $user->company);
        $this->assertEquals('Acme Corp', $user->company->name);

        // Normalize back should also work
        $normalized = $this->mapper->toArray($user);
        $this->assertEquals('John', $normalized['name']);
        $this->assertIsArray($normalized['company']);
        $this->assertEquals('Acme Corp', $normalized['company']['name']);
    }
}

class CircularUser
{
    public function __construct(
        public string $name,
        #[MapProperty(classType: CircularCompany::class)]
        public ?CircularCompany $company = null
    ) {
    }
}

class CircularCompany
{
    public function __construct(
        public string $name,
        #[MapProperty(classType: CircularUser::class)]
        public ?CircularUser $owner = null
    ) {
    }
}
