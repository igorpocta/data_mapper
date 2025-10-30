<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Types;

use InvalidArgumentException;

class IntType extends AbstractType
{
    public function getName(): string
    {
        return 'int';
    }

    public function getAliases(): array
    {
        return ['int', 'integer'];
    }

    protected function denormalizeValue(mixed $value, string $fieldName): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException(
            "Cannot cast value of field '{$fieldName}' to integer"
        );
    }

    protected function normalizeValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        // This should not happen in normal usage, but handle it for type safety
        return 0;
    }
}
