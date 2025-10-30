<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\Filters\ToTimezoneFilter;
use Pocta\DataMapper\Attributes\Filters\StartOfDayFilter;
use Pocta\DataMapper\Attributes\Filters\EndOfDayFilter;
use Pocta\DataMapper\Attributes\Filters\TruncateDateTimeFilter;
use Pocta\DataMapper\Attributes\Filters\AddIntervalFilter;
use Pocta\DataMapper\Attributes\Filters\SubIntervalFilter;
use Pocta\DataMapper\Attributes\Filters\ToUnixTimestampFilter;
use Pocta\DataMapper\Attributes\Filters\EnsureImmutableFilter;

class DateTimeFiltersTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testTimezoneStartEndTruncate(): void
    {
        // Define a class for mapping from array (so filters apply during denormalization)
        $class = new class {
            public static function name(): string { return __CLASS__; }
            #[EnsureImmutableFilter]
            #[ToTimezoneFilter('Europe/Prague')]
            #[StartOfDayFilter]
            public \DateTimeImmutable $a;

            #[TruncateDateTimeFilter('hour')]
            public \DateTimeImmutable $b;
        };

        $object = $this->mapper->fromArray([
            'a' => '2024-01-05T12:34:56+00:00',
            'b' => '2024-01-05T12:34:56+00:00'
        ], get_class($class));

        $data = $this->mapper->toArray($object);
        $this->assertIsString($data['a']);
        $this->assertStringContainsString('T00:00:00', $data['a']);
        $this->assertIsString($data['b']);
        $this->assertStringContainsString('T12:00:00', $data['b']);
    }

    public function testToUnixTimestamp(): void
    {
        $obj = new class {
            #[ToUnixTimestampFilter]
            public \DateTimeImmutable $a;

            public function __construct()
            {
                $this->a = new \DateTimeImmutable('2024-01-01 12:00:00', new \DateTimeZone('UTC'));
            }
        };
        $data = $this->mapper->toArray($obj);
        $this->assertIsInt($data['a']);
        $this->assertSame(strtotime('2024-01-01 12:00:00 UTC'), $data['a']);
    }

    public function testAddSubInterval(): void
    {
        $class = new class {
            public static function name(): string { return __CLASS__; }
            #[AddIntervalFilter('P1D')]
            #[SubIntervalFilter('PT12H')]
            public \DateTimeImmutable $a;
        };
        $object = $this->mapper->fromArray([
            'a' => '2024-01-01T00:00:00+00:00'
        ], get_class($class));
        $data = $this->mapper->toArray($object);
        $this->assertIsString($data['a']);
        $this->assertStringContainsString('T12:00:00', $data['a']);
    }
}
