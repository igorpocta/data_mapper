<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Attributes\PropertyType;
use Pocta\DataMapper\Mapper;

class ArrayOfScalarsTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testArrayOfIntegers(): void
    {
        $data = [
            'scores' => [10, 20, 30, 40, 50],
        ];

        $result = $this->mapper->fromArray($data, TestScoresDto::class);

        $this->assertInstanceOf(TestScoresDto::class, $result);
        $this->assertIsArray($result->scores);
        $this->assertCount(5, $result->scores);
        $this->assertEquals([10, 20, 30, 40, 50], $result->scores);
        $this->assertContainsOnly('int', $result->scores);
    }

    public function testArrayOfStrings(): void
    {
        $data = [
            'tags' => ['php', 'testing', 'data-mapper'],
        ];

        $result = $this->mapper->fromArray($data, TestTagsDto::class);

        $this->assertInstanceOf(TestTagsDto::class, $result);
        $this->assertIsArray($result->tags);
        $this->assertCount(3, $result->tags);
        $this->assertEquals(['php', 'testing', 'data-mapper'], $result->tags);
        $this->assertContainsOnly('string', $result->tags);
    }

    public function testArrayOfFloats(): void
    {
        $data = [
            'prices' => [10.5, 20.99, 30.0],
        ];

        $result = $this->mapper->fromArray($data, TestPricesDto::class);

        $this->assertInstanceOf(TestPricesDto::class, $result);
        $this->assertIsArray($result->prices);
        $this->assertCount(3, $result->prices);
        $this->assertEquals([10.5, 20.99, 30.0], $result->prices);
        $this->assertContainsOnly('float', $result->prices);
    }

    public function testArrayOfBooleans(): void
    {
        $data = [
            'flags' => [true, false, true, true],
        ];

        $result = $this->mapper->fromArray($data, TestFlagsDto::class);

        $this->assertInstanceOf(TestFlagsDto::class, $result);
        $this->assertIsArray($result->flags);
        $this->assertCount(4, $result->flags);
        $this->assertEquals([true, false, true, true], $result->flags);
        $this->assertContainsOnly('bool', $result->flags);
    }

    public function testArrayOfIntegersWithStringValues(): void
    {
        $data = [
            'scores' => ['10', '20', '30'],
        ];

        $result = $this->mapper->fromArray($data, TestScoresDto::class);

        $this->assertInstanceOf(TestScoresDto::class, $result);
        $this->assertIsArray($result->scores);
        $this->assertCount(3, $result->scores);
        $this->assertEquals([10, 20, 30], $result->scores);
        $this->assertContainsOnly('int', $result->scores);
    }

    public function testEmptyArrayOfIntegers(): void
    {
        $data = [
            'scores' => [],
        ];

        $result = $this->mapper->fromArray($data, TestScoresDto::class);

        $this->assertInstanceOf(TestScoresDto::class, $result);
        $this->assertIsArray($result->scores);
        $this->assertEmpty($result->scores);
    }

    public function testNormalizationOfArrayOfIntegers(): void
    {
        $dto = new TestScoresDto([10, 20, 30]);
        $result = $this->mapper->toArray($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('scores', $result);
        $this->assertEquals([10, 20, 30], $result['scores']);
    }

    public function testNormalizationOfArrayOfStrings(): void
    {
        $dto = new TestTagsDto(['php', 'testing']);
        $result = $this->mapper->toArray($dto);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertEquals(['php', 'testing'], $result['tags']);
    }
}

class TestScoresDto
{
    public function __construct(
        /** @var int[] */
        #[MapProperty(type: PropertyType::Array, arrayOf: 'int')]
        public array $scores = []
    ) {
    }
}

class TestTagsDto
{
    public function __construct(
        /** @var string[] */
        #[MapProperty(type: PropertyType::Array, arrayOf: 'string')]
        public array $tags = []
    ) {
    }
}

class TestPricesDto
{
    public function __construct(
        /** @var float[] */
        #[MapProperty(type: PropertyType::Array, arrayOf: 'float')]
        public array $prices = []
    ) {
    }
}

class TestFlagsDto
{
    public function __construct(
        /** @var bool[] */
        #[MapProperty(type: PropertyType::Array, arrayOf: 'bool')]
        public array $flags = []
    ) {
    }
}
