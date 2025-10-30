<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\Filters\EachFilter;
use Pocta\DataMapper\Attributes\Filters\StringTrimFilter;
use Pocta\DataMapper\Attributes\Filters\UniqueArrayFilter;
use Pocta\DataMapper\Attributes\Filters\SortArrayFilter;
use Pocta\DataMapper\Attributes\Filters\SortArrayByKeyFilter;
use Pocta\DataMapper\Attributes\Filters\ReverseArrayFilter;
use Pocta\DataMapper\Attributes\Filters\FilterKeysFilter;
use Pocta\DataMapper\Attributes\Filters\SliceArrayFilter;
use Pocta\DataMapper\Attributes\Filters\LimitArrayFilter;
use Pocta\DataMapper\Attributes\Filters\FlattenArrayFilter;
use Pocta\DataMapper\Attributes\Filters\ArrayCastFilter;

class ArrayFiltersTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testEachUniqueSort(): void
    {
        $obj = new class {
            /**
             * @var array<string>
             */
            #[EachFilter(StringTrimFilter::class)]
            #[UniqueArrayFilter]
            #[SortArrayFilter]
            public array $arr = [' b', 'a ', 'a', ' c '];
        };
        $data = $this->mapper->toArray($obj);
        $this->assertSame(['a', 'b', 'c'], $data['arr']);
    }

    public function testByKeyReverseSliceLimitFlattenCast(): void
    {
        $obj = new class {
            /**
             * @var array<string, int>
             */
            #[SortArrayByKeyFilter]
            #[ReverseArrayFilter]
            public array $assoc = ['b' => 2, 'a' => 1, 'c' => 3];

            /**
             * @var array<string, int>
             */
            #[FilterKeysFilter(allow: ['a','c'])]
            public array $only = ['a' => 1, 'b' => 2, 'c' => 3];

            /**
             * @var array<int>
             */
            #[SliceArrayFilter(1, 2, preserveKeys: false)]
            public array $slice = [1, 2, 3, 4];

            /**
             * @var array<int>
             */
            #[LimitArrayFilter(2, preserveKeys: false)]
            public array $limit = [1, 2, 3];

            /**
             * @var array<mixed>
             */
            #[FlattenArrayFilter]
            public array $flat = [1, [2, [3]]];

            /**
             * @var array<mixed>
             */
            #[ArrayCastFilter('int', recursive: true)]
            public array $cast = ['1', ['2', ['3']]];
        };

        $data = $this->mapper->toArray($obj);
        $this->assertSame(['c' => 3, 'b' => 2, 'a' => 1], $data['assoc']);
        $this->assertSame(['a' => 1, 'c' => 3], $data['only']);
        $this->assertSame([2, 3], $data['slice']);
        $this->assertSame([1, 2], $data['limit']);
        $this->assertSame([1, 2, 3], $data['flat']);
        $this->assertSame([1, [2, [3]]], $data['cast']);
    }
}
