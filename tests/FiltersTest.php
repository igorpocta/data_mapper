<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Tests\Fixtures\FilteredItem;

class FiltersTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testStringFilters(): void
    {
        // Verify filters are applied during normalization (object -> array)
        $item = new FilteredItem();
        $item->name = '  AbC  ';
        $item->code = 'abc-123';
        $item->oneLine = "Hello\nWorld\r\n";
        $item->text = '<p>Hello <b>World</b></p>';
        $item->richText = '<p>Hello <b>World</b></p>';
        $item->intish = '42';
        $item->floatish = ' 1 234,50 ';
        $item->optional = '';
        $item->sluggy = 'Hello    World';
        $item->stringified = ' 123 ';

        $data = $this->mapper->toArray($item);

        $this->assertSame('abc', $data['name']);
        $this->assertSame('ABC-123', $data['code']);
        $this->assertSame('HelloWorld', $data['oneLine']);
        $this->assertSame('Hello World', $data['text']);
        $this->assertSame('Hello <b>World</b>', $data['richText']);
        $this->assertSame(42, $data['intish']);
        $this->assertSame(1234.5, $data['floatish']);
        // optional was empty string -> becomes null; still present because source wasn't null
        $this->assertArrayHasKey('optional', $data);
        $this->assertNull($data['optional']);
        $this->assertSame('Hello-World', $data['sluggy']);
        $this->assertSame('123', $data['stringified']);
        // And also during denormalization (array -> object)
        $object = $this->mapper->fromArray([
            'name' => '  AbC  ',
            'code' => 'abc-123',
            'oneLine' => "Hello\nWorld\r\n",
            'text' => '<p>Hello <b>World</b></p>',
            'richText' => '<p>Hello <b>World</b></p>',
            'intish' => '42',
            'floatish' => ' 1 234,50 ',
            'optional' => '',
            'sluggy' => 'Hello    World',
            'stringified' => ' 123 ',
        ], FilteredItem::class);

        $this->assertSame('abc', $object->name);
        $this->assertSame('ABC-123', $object->code);
        $this->assertSame('HelloWorld', $object->oneLine);
        $this->assertSame('Hello World', $object->text);
        $this->assertSame('Hello <b>World</b>', $object->richText);
        $this->assertSame('42', $object->intish);
        $this->assertSame('1234.5', $object->floatish);
        $this->assertNull($object->optional);
        $this->assertSame('Hello-World', $object->sluggy);
        $this->assertSame('123', $object->stringified);
    }
}
