<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\Filters\JsonDecodeFilter;
use Pocta\DataMapper\Attributes\Filters\JsonEncodeFilter;
use Pocta\DataMapper\Attributes\Filters\UrlEncodeFilter;
use Pocta\DataMapper\Attributes\Filters\UrlDecodeFilter;
use Pocta\DataMapper\Attributes\Filters\HtmlEntitiesEncodeFilter;
use Pocta\DataMapper\Attributes\Filters\HtmlEntitiesDecodeFilter;

class FormattingFiltersTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testJsonEncodeDecode(): void
    {
        $obj = new class {
            /**
             * @var array<string, int>
             */
            #[JsonEncodeFilter]
            public array $a = ['x' => 1];

            #[JsonDecodeFilter]
            public string $b = '{"y":2}';
        };
        $data = $this->mapper->toArray($obj);
        $this->assertSame('{"x":1}', $data['a']);
        $this->assertSame(['y' => 2], $data['b']);
    }

    public function testUrlHtmlEntities(): void
    {
        $obj = new class {
            #[UrlEncodeFilter]
            public string $u1 = 'a b';

            #[UrlDecodeFilter]
            public string $u2 = 'a%20b';

            #[HtmlEntitiesEncodeFilter]
            public string $h1 = '<b>&</b>';

            #[HtmlEntitiesDecodeFilter]
            public string $h2 = '&lt;b&gt;&amp;&lt;/b&gt;';
        };
        $data = $this->mapper->toArray($obj);
        $this->assertSame('a%20b', $data['u1']);
        $this->assertSame('a b', $data['u2']);
        $this->assertSame('&lt;b&gt;&amp;&lt;/b&gt;', $data['h1']);
        $this->assertSame('<b>&</b>', $data['h2']);
    }
}

