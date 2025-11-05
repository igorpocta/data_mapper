<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Attributes\PropertyType;
use Pocta\DataMapper\Mapper;

class ArrayOfInvalidTypesTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testArrayOfArrayShouldFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('arrayOf cannot be PropertyType::Array');

        $data = ['items' => [[1, 2], [3, 4]]];
        $this->mapper->fromArray($data, InvalidArrayOfArrayDto::class);
    }

    public function testArrayOfDateTimeShouldFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('arrayOf cannot be PropertyType::DateTime');

        // This will fail when mapper tries to read the attribute
        $data = ['dates' => []];
        $this->mapper->fromArray($data, ArrayOfDateTimeDto::class);
    }
}

class InvalidArrayOfArrayDto
{
    public function __construct(
        /** @var array<mixed> */
        #[MapProperty(type: PropertyType::Array, arrayOf: PropertyType::Array)]
        public array $items = []
    ) {
    }
}

class ArrayOfDateTimeDto
{
    public function __construct(
        /** @var \DateTime[] */
        #[MapProperty(type: PropertyType::Array, arrayOf: PropertyType::DateTime)]
        public array $dates = []
    ) {
    }
}
