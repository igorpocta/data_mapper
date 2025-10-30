<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Attributes\PropertyType;

class MixedArrayTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testFromArrayWithMixedArray(): void
    {
        $data = [
            'id' => 1,
            'metadata' => [
                'key1' => 'value1',
                'key2' => 123,
                'key3' => true,
                'nested' => [
                    'inner' => 'data'
                ]
            ]
        ];

        $object = $this->mapper->fromArray($data, MixedArrayClass::class);

        $this->assertSame(1, $object->id);
        $this->assertSame('value1', $object->metadata['key1']);
        $this->assertSame(123, $object->metadata['key2']);
        $this->assertTrue($object->metadata['key3']);
        $this->assertIsArray($object->metadata['nested']);
        $this->assertSame('data', $object->metadata['nested']['inner']);
    }

    public function testToArrayWithMixedArray(): void
    {
        $object = new MixedArrayClass();
        $object->id = 2;
        $object->metadata = [
            'foo' => 'bar',
            'number' => 456,
            'bool' => false,
            'array' => [1, 2, 3]
        ];

        $data = $this->mapper->toArray($object);

        $this->assertSame(2, $data['id']);
        $this->assertIsArray($data['metadata']);
        $this->assertSame('bar', $data['metadata']['foo']);
        $this->assertSame(456, $data['metadata']['number']);
        $this->assertFalse($data['metadata']['bool']);
        $this->assertSame([1, 2, 3], $data['metadata']['array']);
    }

    public function testRoundTripWithMixedArray(): void
    {
        $originalData = [
            'id' => 3,
            'metadata' => [
                'string' => 'test',
                'int' => 999,
                'float' => 12.34,
                'bool' => true,
                'null' => null,
                'nested' => [
                    'deep' => [
                        'value' => 'deep value'
                    ]
                ]
            ]
        ];

        $object = $this->mapper->fromArray($originalData, MixedArrayClass::class);
        $resultData = $this->mapper->toArray($object);

        $this->assertSame($originalData['id'], $resultData['id']);
        $this->assertSame($originalData['metadata'], $resultData['metadata']);
    }

    public function testMixedArrayWithEmptyArray(): void
    {
        $data = [
            'id' => 4,
            'metadata' => []
        ];

        $object = $this->mapper->fromArray($data, MixedArrayClass::class);

        $this->assertSame(4, $object->id);
        $this->assertEmpty($object->metadata);
    }

    public function testMixedArrayWithNumericKeys(): void
    {
        $data = [
            'id' => 5,
            'metadata' => [
                0 => 'first',
                1 => 'second',
                2 => 'third'
            ]
        ];

        $object = $this->mapper->fromArray($data, MixedArrayClass::class);

        $this->assertSame(5, $object->id);
        $this->assertSame('first', $object->metadata[0]);
        $this->assertSame('second', $object->metadata[1]);
        $this->assertSame('third', $object->metadata[2]);
    }
}

class MixedArrayClass
{
    public int $id;

    /**
     * @var array<int|string, mixed>
     */
    #[MapProperty(type: PropertyType::Array)]
    public array $metadata;
}
