<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Exceptions\ValidationException;

class FloatTypeTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testFromArrayWithFloat(): void
    {
        $data = [
            'price' => 19.99,
            'discount' => 0.15
        ];

        $object = $this->mapper->fromArray($data, FloatTestClass::class);

        $this->assertSame(19.99, $object->price);
        $this->assertSame(0.15, $object->discount);
    }

    public function testFromArrayWithStringFloat(): void
    {
        $data = [
            'price' => '29.99',
            'discount' => '0.25'
        ];

        $object = $this->mapper->fromArray($data, FloatTestClass::class);

        $this->assertSame(29.99, $object->price);
        $this->assertSame(0.25, $object->discount);
    }

    public function testFromArrayWithInteger(): void
    {
        $data = [
            'price' => 10,
            'discount' => 1
        ];

        $object = $this->mapper->fromArray($data, FloatTestClass::class);

        $this->assertSame(10.0, $object->price);
        $this->assertSame(1.0, $object->discount);
    }

    public function testToArrayWithFloat(): void
    {
        $object = new FloatTestClass();
        $object->price = 99.99;
        $object->discount = 0.5;

        $data = $this->mapper->toArray($object);

        $this->assertIsFloat($data['price']);
        $this->assertIsFloat($data['discount']);
        $this->assertSame(99.99, $data['price']);
        $this->assertSame(0.5, $data['discount']);
    }

    public function testFromArrayWithInvalidFloat(): void
    {
        $data = [
            'price' => 'not a number',
            'discount' => 0.15
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Cannot cast value of field 'price' to float");

        $this->mapper->fromArray($data, FloatTestClass::class);
    }
}

class FloatTestClass
{
    public float $price;
    public float $discount;
}
