<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Types;

use InvalidArgumentException;

class BoolType extends AbstractType
{
    public function getName(): string
    {
        return 'bool';
    }

    public function getAliases(): array
    {
        return ['bool', 'boolean'];
    }

    protected function denormalizeValue(mixed $value, string $fieldName): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['true', '1', 'yes'], true)) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'no', ''], true)) {
                return false;
            }
        }

        throw new InvalidArgumentException(
            "Cannot cast value of field '{$fieldName}' to boolean"
        );
    }

    protected function normalizeValue(mixed $value): bool
    {
        return (bool) $value;
    }
}
