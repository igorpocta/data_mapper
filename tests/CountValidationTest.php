<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Exceptions\ValidationException;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\MapperOptions;
use Pocta\DataMapper\Validation\Count;

class CountValidationTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper(MapperOptions::strict()->withAutoValidation());
    }

    public function testCountMinValid(): void
    {
        $data = ['items' => ['a', 'b']];
        $obj = $this->mapper->fromArray($data, CountMinTestDTO::class);
        $this->assertCount(2, $obj->items);
    }

    public function testCountMinInvalid(): void
    {
        $this->expectException(ValidationException::class);
        $data = ['items' => []];
        $this->mapper->fromArray($data, CountMinTestDTO::class);
    }

    public function testCountMaxValid(): void
    {
        $data = ['items' => ['a', 'b']];
        $obj = $this->mapper->fromArray($data, CountMaxTestDTO::class);
        $this->assertCount(2, $obj->items);
    }

    public function testCountMaxInvalid(): void
    {
        $this->expectException(ValidationException::class);
        $data = ['items' => ['a', 'b', 'c', 'd']];
        $this->mapper->fromArray($data, CountMaxTestDTO::class);
    }

    public function testCountExactlyValid(): void
    {
        $data = ['items' => ['a', 'b', 'c']];
        $obj = $this->mapper->fromArray($data, CountExactlyTestDTO::class);
        $this->assertCount(3, $obj->items);
    }

    public function testCountExactlyInvalid(): void
    {
        $this->expectException(ValidationException::class);
        $data = ['items' => ['a', 'b']];
        $this->mapper->fromArray($data, CountExactlyTestDTO::class);
    }

    public function testCountMinMaxValid(): void
    {
        $data = ['items' => ['a', 'b']];
        $obj = $this->mapper->fromArray($data, CountMinMaxTestDTO::class);
        $this->assertCount(2, $obj->items);
    }

    public function testCountMinMaxTooFew(): void
    {
        $this->expectException(ValidationException::class);
        $data = ['items' => []];
        $this->mapper->fromArray($data, CountMinMaxTestDTO::class);
    }

    public function testCountMinMaxTooMany(): void
    {
        $this->expectException(ValidationException::class);
        $data = ['items' => ['a', 'b', 'c', 'd']];
        $this->mapper->fromArray($data, CountMinMaxTestDTO::class);
    }

    public function testCountNullSkipped(): void
    {
        $data = ['items' => null];
        $obj = $this->mapper->fromArray($data, CountNullableTestDTO::class);
        $this->assertNull($obj->items);
    }

    public function testCountCustomMessage(): void
    {
        try {
            $data = ['items' => ['a', 'b', 'c', 'd']];
            $this->mapper->fromArray($data, CountCustomMessageTestDTO::class);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Maximum 3 children allowed', $e->getMessage());
        }
    }

    public function testCountDirectValidator(): void
    {
        $validator = new \Pocta\DataMapper\Validation\Validator();
        $obj = new CountMinTestDTO(items: []);
        $errors = $validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('items', $errors);
    }
}

class CountMinTestDTO
{
    public function __construct(
        #[Count(min: 1)]
        public array $items = [],
    ) {}
}

class CountMaxTestDTO
{
    public function __construct(
        #[Count(max: 3)]
        public array $items = [],
    ) {}
}

class CountExactlyTestDTO
{
    public function __construct(
        #[Count(exactly: 3)]
        public array $items = [],
    ) {}
}

class CountMinMaxTestDTO
{
    public function __construct(
        #[Count(min: 1, max: 3)]
        public array $items = [],
    ) {}
}

class CountNullableTestDTO
{
    public function __construct(
        #[Count(min: 1)]
        public ?array $items = null,
    ) {}
}

class CountCustomMessageTestDTO
{
    public function __construct(
        #[Count(max: 3, message: 'Maximum 3 children allowed')]
        public array $items = [],
    ) {}
}
