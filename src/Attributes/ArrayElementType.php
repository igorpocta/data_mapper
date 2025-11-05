<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes;

/**
 * Enum defining scalar types allowed for array elements in arrayOf parameter.
 * For arrays of objects, use the class name directly.
 */
enum ArrayElementType: string
{
    case Int = 'int';
    case Integer = 'integer';
    case String = 'string';
    case Bool = 'bool';
    case Boolean = 'boolean';
    case Float = 'float';
    case Double = 'double';
}
