<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\Filters\ToBoolStrictFilter;
use Pocta\DataMapper\Attributes\Filters\NullIfFalseFilter;
use Pocta\DataMapper\Attributes\Filters\NullIfTrueFilter;

class BooleanFiltersTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testToBoolStrictAndNullIf(): void
    {
        $obj = new class {
            #[ToBoolStrictFilter]
            public bool $a = true;

            #[ToBoolStrictFilter]
            #[NullIfTrueFilter]
            public ?bool $b = true;

            #[ToBoolStrictFilter]
            #[NullIfFalseFilter]
            public ?bool $c = false;
        };
        $data = $this->mapper->toArray($obj);
        $this->assertTrue($data['a']);
        $this->assertNull($data['b']);
        $this->assertNull($data['c']);

        // denormalization
        $object = $this->mapper->fromArray([
            'a' => 'no', 'b' => 'true', 'c' => 'false'
        ], get_class($obj));
        $this->assertFalse($object->a);
        $this->assertNull($object->b);
        $this->assertNull($object->c);
    }
}
