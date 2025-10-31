<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes;

use Attribute;

/**
 * Maps a property to a CSV column by name or index.
 *
 * Examples:
 * ```php
 * class Product
 * {
 *     #[MapCsvColumn('product_name')]
 *     public string $name;
 *
 *     #[MapCsvColumn(index: 0)]
 *     public string $id;
 *
 *     #[MapCsvColumn('price', index: 2)]
 *     public float $price;
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapCsvColumn
{
    /**
     * @param string|null $name CSV column name (header)
     * @param int|null $index CSV column index (0-based)
     */
    public function __construct(
        public ?string $name = null,
        public ?int $index = null
    ) {
        if ($name === null && $index === null) {
            throw new \InvalidArgumentException('Either name or index must be specified');
        }
    }
}
