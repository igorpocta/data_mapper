<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Types;

use InvalidArgumentException;

class FloatType extends AbstractType
{
    public function getName(): string
    {
        return 'float';
    }

    public function getAliases(): array
    {
        return ['float', 'double'];
    }

    protected function denormalizeValue(mixed $value, string $fieldName): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        throw new InvalidArgumentException(
            "Cannot cast value of field '{$fieldName}' to float"
        );
    }

    protected function normalizeValue(mixed $value): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // This should not happen in normal usage, but handle it for type safety
        return 0.0;
    }
}
