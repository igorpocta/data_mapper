<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Tests\Fixtures\ProductWithNullable;
use InvalidArgumentException;

class NullablePropertiesTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testFromArrayWithNullValues(): void
    {
        $data = ['id' => 1, 'name' => 'Product', 'description' => null, 'stock' => null, 'featured' => null];

        $product = $this->mapper->fromArray($data, ProductWithNullable::class);

        $this->assertSame(1, $product->getId());
        $this->assertSame('Product', $product->getName());
        $this->assertNull($product->getDescription());
        $this->assertNull($product->getStock());
        $this->assertNull($product->isFeatured());
    }

    public function testFromArrayWithMixedNullAndValues(): void
    {
        $data = ['id' => 2, 'name' => 'Product 2', 'description' => 'A product', 'stock' => null, 'featured' => true];

        $product = $this->mapper->fromArray($data, ProductWithNullable::class);

        $this->assertSame(2, $product->getId());
        $this->assertSame('Product 2', $product->getName());
        $this->assertSame('A product', $product->getDescription());
        $this->assertNull($product->getStock());
        $this->assertTrue($product->isFeatured());
    }

    public function testFromArrayWithAllValues(): void
    {
        $data = ['id' => 3, 'name' => 'Product 3', 'description' => 'Description', 'stock' => 100, 'featured' => false];

        $product = $this->mapper->fromArray($data, ProductWithNullable::class);

        $this->assertSame(3, $product->getId());
        $this->assertSame('Product 3', $product->getName());
        $this->assertSame('Description', $product->getDescription());
        $this->assertSame(100, $product->getStock());
        $this->assertFalse($product->isFeatured());
    }

    public function testFromArrayThrowsExceptionWhenNullNotAllowed(): void
    {
        $data = ['id' => null, 'name' => 'Product', 'description' => null, 'stock' => null, 'featured' => null];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'id' does not accept null values");

        $this->mapper->fromArray($data, ProductWithNullable::class);
    }

    public function testToArrayWithNullValues(): void
    {
        $product = new ProductWithNullable();
        $product->setId(10);
        $product->setName('Test Product');
        $product->setDescription(null);
        $product->setStock(null);
        $product->setFeatured(null);

        $data = $this->mapper->toArray($product);

        $this->assertSame(10, $data['id']);
        $this->assertSame('Test Product', $data['name']);
        // Null values are not included in output by default
        $this->assertArrayNotHasKey('description', $data);
        $this->assertArrayNotHasKey('stock', $data);
        $this->assertArrayNotHasKey('featured', $data);
    }

    public function testToArrayWithSomeNullValues(): void
    {
        $product = new ProductWithNullable();
        $product->setId(11);
        $product->setName('Another Product');
        $product->setDescription('Has description');
        $product->setStock(null);
        $product->setFeatured(true);

        $data = $this->mapper->toArray($product);

        $this->assertSame(11, $data['id']);
        $this->assertSame('Another Product', $data['name']);
        $this->assertSame('Has description', $data['description']);
        $this->assertArrayNotHasKey('stock', $data);
        $this->assertTrue($data['featured']);
    }

    public function testRoundTripWithNullableProperties(): void
    {
        $originalData = ['id' => 20, 'name' => 'Round Trip', 'description' => 'Test', 'stock' => 50, 'featured' => true];

        $product = $this->mapper->fromArray($originalData, ProductWithNullable::class);
        $resultData = $this->mapper->toArray($product);

        $this->assertSame(20, $resultData['id']);
        $this->assertSame('Round Trip', $resultData['name']);
        $this->assertSame('Test', $resultData['description']);
        $this->assertSame(50, $resultData['stock']);
        $this->assertTrue($resultData['featured']);
    }

    public function testFromArrayWithMissingOptionalFields(): void
    {
        $data = ['id' => 30, 'name' => 'Minimal Product'];

        $product = $this->mapper->fromArray($data, ProductWithNullable::class);

        $this->assertSame(30, $product->getId());
        $this->assertSame('Minimal Product', $product->getName());
    }

    public function testToArrayWithAllNullOptionalFields(): void
    {
        $product = new ProductWithNullable();
        $product->setId(40);
        $product->setName('Only Required');
        $product->setDescription(null);
        $product->setStock(null);
        $product->setFeatured(null);

        $data = $this->mapper->toArray($product);

        $this->assertCount(2, $data);
        $this->assertSame(40, $data['id']);
        $this->assertSame('Only Required', $data['name']);
    }

    public function testFromJsonWithNullValuesStillWorks(): void
    {
        $data = ['id' => 50, 'name' => 'JSON Test', 'description' => null, 'stock' => null, 'featured' => null];
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        $product = $this->mapper->fromJson($json, ProductWithNullable::class);

        $this->assertSame(50, $product->getId());
        $this->assertSame('JSON Test', $product->getName());
        $this->assertNull($product->getDescription());
    }

    public function testToJsonWithNullValuesStillWorks(): void
    {
        $product = new ProductWithNullable();
        $product->setId(60);
        $product->setName('JSON Output');
        $product->setDescription(null);
        $product->setStock(100);
        $product->setFeatured(true);

        $json = $this->mapper->toJson($product);
        $data = json_decode($json, true);

        $this->assertIsArray($data);
        $this->assertSame(60, $data['id']);
        $this->assertSame('JSON Output', $data['name']);
        $this->assertArrayNotHasKey('description', $data);
        $this->assertSame(100, $data['stock']);
        $this->assertTrue($data['featured']);
    }
}
