<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\MapFrom;
use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\ObjectPathResolver;
use ArrayObject;

// Test source entities/objects
class UserEntity
{
    private string $firstName = 'John';
    private string $lastName = 'Doe';
    private bool $active = true;
    private ?AddressEntity $address = null;
    /** @var array<AddressEntity> */
    private array $addresses = [];
    private string $email = 'john@example.com';

    public function __construct()
    {
        $this->address = new AddressEntity();
        $this->addresses = [
            new AddressEntity('Main St', 'New York'),
            new AddressEntity('Second Ave', 'Boston'),
        ];
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getAddress(): ?AddressEntity
    {
        return $this->address;
    }

    /**
     * @return array<AddressEntity>
     */
    public function getAddresses(): array
    {
        return $this->addresses;
    }

    public function email(): string
    {
        return $this->email;
    }
}

class AddressEntity
{
    private string $street;
    private string $city;

    public function __construct(string $street = '123 Main St', string $city = 'Springfield')
    {
        $this->street = $street;
        $this->city = $city;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getFullAddress(): string
    {
        return $this->street . ', ' . $this->city;
    }
}

class ProductEntity
{
    public string $name = 'Product A';
    private int $stock = 100;

    public function getStock(): int
    {
        return $this->stock;
    }

    public function hasStock(): bool
    {
        return $this->stock > 0;
    }
}

// Target DTOs
class UserDTO
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public bool $active
    ) {
    }
}

class UserWithAddressDTO
{
    public function __construct(
        #[MapFrom('firstName')]
        public string $name,
        #[MapFrom('address.street')]
        public string $street,
        #[MapFrom('address.city')]
        public string $city
    ) {
    }
}

class UserWithFullNameDTO
{
    public function __construct(
        #[MapFrom('getFullName()')]
        public string $fullName,
        public bool $active
    ) {
    }
}

class UserWithMethodCallDTO
{
    public function __construct(
        #[MapFrom('email()')]
        public string $emailAddress
    ) {
    }
}

class UserWithArrayIndexDTO
{
    public function __construct(
        #[MapFrom('addresses[0].street')]
        public string $firstStreet,
        #[MapFrom('addresses[1].city')]
        public string $secondCity
    ) {
    }
}

class UserWithNestedMethodDTO
{
    public function __construct(
        #[MapFrom('address.getFullAddress()')]
        public string $fullAddress
    ) {
    }
}

class ProductDTO
{
    public function __construct(
        public string $name,
        public int $stock,
        #[MapFrom('hasStock')]
        public bool $inStock
    ) {
    }
}

class UserWithPublicPropertyDTO
{
    public function __construct(
        #[MapFrom('firstName')]
        public string $name
    ) {
    }
}

class ComplexMappingDTO
{
    public function __construct(
        #[MapFrom('getFullName()')]
        public string $fullName,
        #[MapFrom('address.street')]
        public string $street,
        #[MapFrom('addresses[0].city')]
        public string $primaryCity,
        public bool $active
    ) {
    }
}

class ObjectMappingTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testBasicObjectMapping(): void
    {
        $user = new UserEntity();

        $dto = $this->mapper->fromObject($user, UserDTO::class);

        $this->assertInstanceOf(UserDTO::class, $dto);
        $this->assertSame('John', $dto->firstName);
        $this->assertSame('Doe', $dto->lastName);
        $this->assertTrue($dto->active);
    }

    public function testGetterMethodResolution(): void
    {
        $user = new UserEntity();

        $dto = $this->mapper->fromObject($user, UserDTO::class);

        // firstName and lastName are resolved via getters
        $this->assertSame('John', $dto->firstName);
        $this->assertSame('Doe', $dto->lastName);
    }

    public function testIsMethodResolution(): void
    {
        $user = new UserEntity();

        $dto = $this->mapper->fromObject($user, UserDTO::class);

        // active is resolved via isActive()
        $this->assertTrue($dto->active);
    }

    public function testNestedObjectPath(): void
    {
        $user = new UserEntity();

        $dto = $this->mapper->fromObject($user, UserWithAddressDTO::class);

        $this->assertSame('John', $dto->name);
        $this->assertSame('123 Main St', $dto->street);
        $this->assertSame('Springfield', $dto->city);
    }

    public function testExplicitMethodCall(): void
    {
        $user = new UserEntity();

        $dto = $this->mapper->fromObject($user, UserWithFullNameDTO::class);

        $this->assertSame('John Doe', $dto->fullName);
        $this->assertTrue($dto->active);
    }

    public function testDirectMethodCall(): void
    {
        $user = new UserEntity();

        $dto = $this->mapper->fromObject($user, UserWithMethodCallDTO::class);

        // email() is called as a direct method
        $this->assertSame('john@example.com', $dto->emailAddress);
    }

    public function testArrayIndexAccess(): void
    {
        $user = new UserEntity();

        $dto = $this->mapper->fromObject($user, UserWithArrayIndexDTO::class);

        $this->assertSame('Main St', $dto->firstStreet);
        $this->assertSame('Boston', $dto->secondCity);
    }

    public function testNestedMethodCall(): void
    {
        $user = new UserEntity();

        $dto = $this->mapper->fromObject($user, UserWithNestedMethodDTO::class);

        $this->assertSame('123 Main St, Springfield', $dto->fullAddress);
    }

    public function testHasMethodResolution(): void
    {
        $product = new ProductEntity();

        $dto = $this->mapper->fromObject($product, ProductDTO::class);

        $this->assertSame('Product A', $dto->name);
        $this->assertSame(100, $dto->stock);
        $this->assertTrue($dto->inStock); // Resolved via hasStock()
    }

    public function testPublicPropertyAccess(): void
    {
        $product = new ProductEntity();

        $dto = $this->mapper->fromObject($product, ProductDTO::class);

        // name is accessed as public property
        $this->assertSame('Product A', $dto->name);
    }

    public function testComplexMapping(): void
    {
        $user = new UserEntity();

        $dto = $this->mapper->fromObject($user, ComplexMappingDTO::class);

        $this->assertSame('John Doe', $dto->fullName);
        $this->assertSame('123 Main St', $dto->street);
        $this->assertSame('New York', $dto->primaryCity);
        $this->assertTrue($dto->active);
    }

    public function testObjectPathResolverResolve(): void
    {
        $user = new UserEntity();

        // Test basic getter
        $this->assertSame('John', ObjectPathResolver::resolve($user, 'firstName'));

        // Test is method
        $this->assertTrue(ObjectPathResolver::resolve($user, 'active'));

        // Test nested path
        $this->assertSame('123 Main St', ObjectPathResolver::resolve($user, 'address.street'));

        // Test array index
        $this->assertSame('Boston', ObjectPathResolver::resolve($user, 'addresses[1].city'));

        // Test method call
        $this->assertSame('John Doe', ObjectPathResolver::resolve($user, 'getFullName()'));

        // Test nested method call
        $this->assertSame('123 Main St, Springfield', ObjectPathResolver::resolve($user, 'address.getFullAddress()'));
    }

    public function testObjectPathResolverExists(): void
    {
        $user = new UserEntity();

        // Existing paths
        $this->assertTrue(ObjectPathResolver::exists($user, 'firstName'));
        $this->assertTrue(ObjectPathResolver::exists($user, 'address.street'));
        $this->assertTrue(ObjectPathResolver::exists($user, 'addresses[0].city'));

        // Non-existing paths
        $this->assertFalse(ObjectPathResolver::exists($user, 'nonExistent'));
        $this->assertFalse(ObjectPathResolver::exists($user, 'address.nonExistent'));
        $this->assertFalse(ObjectPathResolver::exists($user, 'addresses[999].city'));
    }

    public function testObjectPathResolverReturnsNullForInvalidPath(): void
    {
        $user = new UserEntity();

        $this->assertNull(ObjectPathResolver::resolve($user, 'nonExistent'));
        $this->assertNull(ObjectPathResolver::resolve($user, 'address.nonExistent'));
        $this->assertNull(ObjectPathResolver::resolve($user, 'addresses[999].city'));
    }

    public function testObjectPathResolverEmptyPath(): void
    {
        $user = new UserEntity();

        $this->assertNull(ObjectPathResolver::resolve($user, ''));
        $this->assertFalse(ObjectPathResolver::exists($user, ''));
    }

    public function testObjectPathResolverInvalidPathSyntax(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid path segment');

        $user = new UserEntity();
        ObjectPathResolver::resolve($user, 'invalid..path');
    }

    public function testObjectPathResolverIsValidPath(): void
    {
        // Valid paths
        $this->assertTrue(ObjectPathResolver::isValidPath('property'));
        $this->assertTrue(ObjectPathResolver::isValidPath('nested.property'));
        $this->assertTrue(ObjectPathResolver::isValidPath('array[0]'));
        $this->assertTrue(ObjectPathResolver::isValidPath('method()'));
        $this->assertTrue(ObjectPathResolver::isValidPath('nested.method()'));
        $this->assertTrue(ObjectPathResolver::isValidPath('array[0].property'));

        // Invalid paths
        $this->assertFalse(ObjectPathResolver::isValidPath(''));
        $this->assertFalse(ObjectPathResolver::isValidPath('invalid..path'));
        $this->assertFalse(ObjectPathResolver::isValidPath('123invalid'));
        $this->assertFalse(ObjectPathResolver::isValidPath('invalid-name'));
    }

    public function testObjectPathResolverWithArrayAccess(): void
    {
        /** @var ArrayObject<string, array<string>> $arrayObject */
        $arrayObject = new ArrayObject([
            'items' => ['value1', 'value2']
        ]);

        // Create a simple object wrapper for testing
        $wrapper = new class($arrayObject) {
            /** @var ArrayObject<string, array<string>> */
            private ArrayObject $data;

            /**
             * @param ArrayObject<string, array<string>> $data
             */
            public function __construct(ArrayObject $data)
            {
                $this->data = $data;
            }

            /**
             * @return array<string>
             */
            public function getItems(): array
            {
                /** @var array<string> */
                $items = $this->data['items'];
                return $items;
            }
        };

        // ArrayAccess should work with nested path and index notation
        $this->assertSame('value1', ObjectPathResolver::resolve($wrapper, 'items[0]'));
        $this->assertSame('value2', ObjectPathResolver::resolve($wrapper, 'items[1]'));
    }

    public function testGetterPriority(): void
    {
        // Test that getter methods have priority over direct property access
        $user = new UserEntity();

        // firstName is private, should be accessed via getFirstName()
        $this->assertSame('John', ObjectPathResolver::resolve($user, 'firstName'));

        // But public properties should also work
        $product = new ProductEntity();
        $this->assertSame('Product A', ObjectPathResolver::resolve($product, 'name'));
    }

    public function testMappingWithNullValues(): void
    {
        // Create a user with null address
        $reflection = new \ReflectionClass(UserEntity::class);
        $user = $reflection->newInstanceWithoutConstructor();

        $firstNameProp = $reflection->getProperty('firstName');
        $firstNameProp->setAccessible(true);
        $firstNameProp->setValue($user, 'Jane');

        $lastNameProp = $reflection->getProperty('lastName');
        $lastNameProp->setAccessible(true);
        $lastNameProp->setValue($user, 'Smith');

        $activeProp = $reflection->getProperty('active');
        $activeProp->setAccessible(true);
        $activeProp->setValue($user, false);

        $addressProp = $reflection->getProperty('address');
        $addressProp->setAccessible(true);
        $addressProp->setValue($user, null);

        $dto = $this->mapper->fromObject($user, UserDTO::class);

        $this->assertSame('Jane', $dto->firstName);
        $this->assertSame('Smith', $dto->lastName);
        $this->assertFalse($dto->active);
    }

    public function testMultipleObjectMappings(): void
    {
        $user1 = new UserEntity();
        $user2 = new UserEntity();

        $dto1 = $this->mapper->fromObject($user1, UserDTO::class);
        $dto2 = $this->mapper->fromObject($user2, UserDTO::class);

        $this->assertInstanceOf(UserDTO::class, $dto1);
        $this->assertInstanceOf(UserDTO::class, $dto2);
        $this->assertNotSame($dto1, $dto2);
    }

    public function testObjectMappingUsesCache(): void
    {
        $user = new UserEntity();

        // First mapping should build metadata
        $dto1 = $this->mapper->fromObject($user, UserDTO::class);

        // Second mapping should use cached metadata
        $dto2 = $this->mapper->fromObject($user, UserDTO::class);

        $this->assertInstanceOf(UserDTO::class, $dto1);
        $this->assertInstanceOf(UserDTO::class, $dto2);
        $this->assertEquals($dto1, $dto2);
    }
}
