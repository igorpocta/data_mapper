<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes;

/**
 * Enum defining available property types for mapping
 */
enum PropertyType: string
{
    case Int = 'int';
    case Integer = 'integer';
    case String = 'string';
    case Bool = 'bool';
    case Boolean = 'boolean';
    case Float = 'float';
    case Double = 'double';
    case DateTime = 'DateTime';
    case DateTimeImmutable = 'DateTimeImmutable';
    case DateTimeInterface = 'DateTimeInterface';
    case Array = 'array';
}
