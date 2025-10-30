<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\Filters\StringTrimFilter;
use Pocta\DataMapper\Attributes\Filters\StringToLowerFilter;
use Pocta\DataMapper\Attributes\Filters\StringToUpperFilter;
use Pocta\DataMapper\Attributes\Filters\TitleCaseFilter;
use Pocta\DataMapper\Attributes\Filters\CapitalizeFirstFilter;
use Pocta\DataMapper\Attributes\Filters\EnsurePrefixFilter;
use Pocta\DataMapper\Attributes\Filters\EnsureSuffixFilter;
use Pocta\DataMapper\Attributes\Filters\SubstringFilter;
use Pocta\DataMapper\Attributes\Filters\TrimLengthFilter;
use Pocta\DataMapper\Attributes\Filters\PadLeftFilter;
use Pocta\DataMapper\Attributes\Filters\PadRightFilter;
use Pocta\DataMapper\Attributes\Filters\ReplaceDiacriticsFilter;
use Pocta\DataMapper\Attributes\Filters\SlugifyFilter;
use Pocta\DataMapper\Attributes\Filters\CollapseWhitespaceFilter;
use Pocta\DataMapper\Attributes\Filters\NormalizeUnicodeFilter;

class StringFiltersTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testBasicStringTransforms(): void
    {
        $obj = new class {
            #[StringTrimFilter]
            #[StringToLowerFilter]
            public string $a = '  HéLLo  ';

            #[StringToUpperFilter]
            public string $b = 'Hello';

            #[TitleCaseFilter]
            public string $c = 'lorem ipsum';

            #[CapitalizeFirstFilter(lowerRest: true)]
            public string $d = 'hELLO';
        };

        $data = $this->mapper->toArray($obj);
        $this->assertSame('héllo', $data['a']);
        $this->assertSame('HELLO', $data['b']);
        $this->assertSame('Lorem Ipsum', $data['c']);
        $this->assertSame('Hello', $data['d']);
    }

    public function testAffixesSubstrPadTrimLength(): void
    {
        $obj = new class {
            #[EnsurePrefixFilter('pre-')]
            #[EnsureSuffixFilter('-suf')]
            public string $x = 'value';

            #[SubstringFilter(1, 3)]
            public string $y = 'abcdef';

            #[TrimLengthFilter(5, '…')]
            public string $z = '123456789';

            #[PadLeftFilter(5, '0')]
            public string $p = '12';

            #[PadRightFilter(5, 'x')]
            public string $q = 'ab';
        };
        $data = $this->mapper->toArray($obj);
        $this->assertSame('pre-value-suf', $data['x']);
        $this->assertSame('bcd', $data['y']);
        $this->assertSame('1234…', $data['z']);
        $this->assertSame('00012', $data['p']);
        $this->assertSame('abxxx', $data['q']);
    }

    public function testWhitespaceDiacriticsSlug(): void
    {
        $obj = new class {
            #[CollapseWhitespaceFilter]
            public string $w = "  Foo\n\t Bar  ";

            #[ReplaceDiacriticsFilter]
            public string $r = 'Příliš žluťoučký kůň';

            #[SlugifyFilter(separator: '-')]
            public string $s = 'Příliš Žluťoučký kůň';

            #[NormalizeUnicodeFilter('NFC')]
            public string $u = "e\u{0301}"; // e + combining acute
        };

        $data = $this->mapper->toArray($obj);
        $this->assertSame('Foo Bar', $data['w']);
        $this->assertIsString($data['r']);
        $this->assertMatchesRegularExpression('/Prilis zlutoucky kun/i', $data['r']);
        $this->assertSame('prilis-zlutoucky-kun', $data['s']);

        // After NFC normalization, composed form should equal "é"
        $this->assertSame('é', $data['u']);
    }
}

