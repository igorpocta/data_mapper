<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Attributes\MapCsvColumn;

class CsvMappingTest extends TestCase
{
    private Mapper $mapper;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
        $this->tempDir = sys_get_temp_dir() . '/data-mapper-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            if ($files !== false) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->tempDir);
        }
    }

    // ========== fromCsv Tests ==========

    public function testFromCsvBasic(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0,
                public string $name = '',
                public float $price = 0.0
            ) {}
        };

        $csv = "id,name,price\n1,Product A,10.50\n2,Product B,20.00";

        $result = $this->mapper->fromCsv($csv, $dto::class);

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('Product A', $result[0]->name);
        $this->assertSame(10.50, $result[0]->price);
        $this->assertSame(2, $result[1]->id);
        $this->assertSame('Product B', $result[1]->name);
        $this->assertSame(20.00, $result[1]->price);
    }

    public function testFromCsvWithCustomDelimiter(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0,
                public string $name = ''
            ) {}
        };

        $csv = "id;name\n1;Product A\n2;Product B";

        $result = $this->mapper->fromCsv($csv, $dto::class, delimiter: ';');

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('Product A', $result[0]->name);
    }

    public function testFromCsvWithMapCsvColumn(): void
    {
        $dto = new class {
            public function __construct(
                #[MapCsvColumn('product_id')]
                public int $id = 0,
                #[MapCsvColumn('product_name')]
                public string $name = '',
                #[MapCsvColumn(index: 2)]
                public float $price = 0.0
            ) {}
        };

        $csv = "product_id,product_name,price\n1,Product A,10.50\n2,Product B,20.00";

        $result = $this->mapper->fromCsv($csv, $dto::class);

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('Product A', $result[0]->name);
        $this->assertSame(10.50, $result[0]->price);
    }

    public function testFromCsvWithoutHeader(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0,
                public string $name = '',
                public float $price = 0.0
            ) {}
        };

        $csv = "1,Product A,10.50\n2,Product B,20.00";

        $result = $this->mapper->fromCsv($csv, $dto::class, hasHeader: false);

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('Product A', $result[0]->name);
        $this->assertSame(10.50, $result[0]->price);
    }

    public function testFromCsvEmptyString(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0
            ) {}
        };

        $result = $this->mapper->fromCsv('', $dto::class);

        $this->assertCount(0, $result);
    }

    public function testFromCsvWithEnclosedValues(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0,
                public string $description = ''
            ) {}
        };

        $csv = "id,description\n1,\"This is a \"\"quoted\"\" value\"\n2,\"Normal value\"";

        $result = $this->mapper->fromCsv($csv, $dto::class);

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('This is a "quoted" value', $result[0]->description);
    }

    // ========== fromCsvFile Tests ==========

    public function testFromCsvFile(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0,
                public string $name = ''
            ) {}
        };

        $filePath = $this->tempDir . '/test.csv';
        file_put_contents($filePath, "id,name\n1,Product A\n2,Product B");

        $result = $this->mapper->fromCsvFile($filePath, $dto::class);

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('Product A', $result[0]->name);
    }

    public function testFromCsvFileNotFound(): void
    {
        $dto = new class {
            public function __construct(public int $id = 0) {}
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CSV file not found');

        $this->mapper->fromCsvFile('/nonexistent/file.csv', $dto::class);
    }

    public function testFromCsvFileLarge(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0,
                public string $name = ''
            ) {}
        };

        $filePath = $this->tempDir . '/large.csv';
        $handle = fopen($filePath, 'w');
        if ($handle === false) {
            $this->fail('Failed to open file for writing');
        }
        fputcsv($handle, ['id', 'name']);
        for ($i = 1; $i <= 100; $i++) {
            fputcsv($handle, [$i, "Product {$i}"]);
        }
        fclose($handle);

        $result = $this->mapper->fromCsvFile($filePath, $dto::class);

        $this->assertCount(100, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame('Product 1', $result[0]->name);
        $this->assertSame(100, $result[99]->id);
        $this->assertSame('Product 100', $result[99]->name);
    }

    // ========== toCsv Tests ==========

    public function testToCsvBasic(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0,
                public string $name = '',
                public float $price = 0.0
            ) {}
        };

        $objects = [
            new ($dto::class)(1, 'Product A', 10.50),
            new ($dto::class)(2, 'Product B', 20.00),
        ];

        $csv = $this->mapper->toCsv($objects);

        $lines = explode("\n", trim($csv));
        $this->assertCount(3, $lines); // header + 2 rows
        $this->assertStringContainsString('id,name,price', $lines[0]);
        $this->assertStringContainsString('1,"Product A",10.5', $lines[1]);
        $this->assertStringContainsString('2,"Product B",20', $lines[2]);
    }

    public function testToCsvWithoutHeader(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0,
                public string $name = ''
            ) {}
        };

        $objects = [
            new ($dto::class)(1, 'Product A'),
        ];

        $csv = $this->mapper->toCsv($objects, includeHeader: false);

        $lines = explode("\n", trim($csv));
        $this->assertCount(1, $lines); // only 1 data row
        $this->assertStringContainsString('1,"Product A"', $lines[0]);
    }

    public function testToCsvWithCustomDelimiter(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0,
                public string $name = ''
            ) {}
        };

        $objects = [
            new ($dto::class)(1, 'Product A'),
        ];

        $csv = $this->mapper->toCsv($objects, delimiter: ';');

        $lines = explode("\n", trim($csv));
        $this->assertStringContainsString('id;name', $lines[0]);
        $this->assertStringContainsString('1;"Product A"', $lines[1]);
    }

    public function testToCsvEmptyCollection(): void
    {
        $csv = $this->mapper->toCsv([]);

        $this->assertSame('', $csv);
    }

    public function testToCsvWithSpecialCharacters(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0,
                public string $description = ''
            ) {}
        };

        $objects = [
            new ($dto::class)(1, 'Text with "quotes"'),
            new ($dto::class)(2, 'Text with, comma'),
            new ($dto::class)(3, "Text with\nnewline"),
        ];

        $csv = $this->mapper->toCsv($objects);

        // Parse it back to verify escaping
        $result = $this->mapper->fromCsv($csv, $dto::class);

        $this->assertCount(3, $result);
        $this->assertSame('Text with "quotes"', $result[0]->description);
        $this->assertSame('Text with, comma', $result[1]->description);
        $this->assertSame("Text with\nnewline", $result[2]->description);
    }

    // ========== Round-trip Tests ==========

    public function testCsvRoundTrip(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0,
                public string $name = '',
                public float $price = 0.0,
                public bool $active = false
            ) {}
        };

        $original = [
            new ($dto::class)(1, 'Product A', 10.50, true),
            new ($dto::class)(2, 'Product B', 20.00, false),
            new ($dto::class)(3, 'Product C', 15.75, true),
        ];

        // Convert to CSV
        $csv = $this->mapper->toCsv($original);

        // Convert back to objects
        $result = $this->mapper->fromCsv($csv, $dto::class);

        $this->assertCount(3, $result);
        $this->assertSame($original[0]->id, $result[0]->id);
        $this->assertSame($original[0]->name, $result[0]->name);
        $this->assertSame($original[0]->price, $result[0]->price);
        $this->assertSame($original[0]->active, $result[0]->active);
    }

    public function testCsvFileRoundTrip(): void
    {
        $dto = new class {
            public function __construct(
                public int $id = 0,
                public string $name = ''
            ) {}
        };

        $original = [
            new ($dto::class)(1, 'Product A'),
            new ($dto::class)(2, 'Product B'),
        ];

        // Convert to CSV and save to file
        $csv = $this->mapper->toCsv($original);
        $filePath = $this->tempDir . '/roundtrip.csv';
        file_put_contents($filePath, $csv);

        // Read from file
        $result = $this->mapper->fromCsvFile($filePath, $dto::class);

        $this->assertCount(2, $result);
        $this->assertSame($original[0]->id, $result[0]->id);
        $this->assertSame($original[0]->name, $result[0]->name);
    }
}
