<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\Filters\ClampFilter;
use Pocta\DataMapper\Attributes\Filters\RoundNumberFilter;
use Pocta\DataMapper\Attributes\Filters\CeilNumberFilter;
use Pocta\DataMapper\Attributes\Filters\FloorNumberFilter;
use Pocta\DataMapper\Attributes\Filters\AbsNumberFilter;
use Pocta\DataMapper\Attributes\Filters\ScaleNumberFilter;
use Pocta\DataMapper\Attributes\Filters\ToDecimalStringFilter;

class NumberFiltersTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testClampRoundAbsScale(): void
    {
        $obj = new class {
            #[ClampFilter(min: 0, max: 10)]
            public float $a = 12.7;

            #[RoundNumberFilter(precision: 2)]
            public float $b = 3.14159;

            #[CeilNumberFilter]
            public float $c = 1.2;

            #[FloorNumberFilter]
            public float $d = 4.8;

            #[AbsNumberFilter]
            public float $e = -7.2;

            #[ScaleNumberFilter(multiplyBy: 2, divideBy: 4)]
            public float $f = 8.0;
        };
        $data = $this->mapper->toArray($obj);
        $this->assertSame(10, $data['a']);
        $this->assertSame(3.14, $data['b']);
        $this->assertSame(2, $data['c']);
        $this->assertSame(4, $data['d']);
        $this->assertSame(7.2, $data['e']);
        $this->assertEquals(4.0, $data['f']);
    }

    public function testToDecimalString(): void
    {
        $obj = new class {
            #[ToDecimalStringFilter(precision: 3)]
            public float $x = 1.23456;
        };
        $data = $this->mapper->toArray($obj);
        $this->assertSame('1.235', $data['x']);
    }
}
