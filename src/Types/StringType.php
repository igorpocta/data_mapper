<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Types;

use InvalidArgumentException;

class StringType extends AbstractType
{
    public function getName(): string
    {
        return 'string';
    }

    public function getAliases(): array
    {
        return ['string'];
    }

    protected function denormalizeValue(mixed $value, string $fieldName): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        throw new InvalidArgumentException(
            "Cannot cast value of field '{$fieldName}' to string"
        );
    }

    protected function normalizeValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        // This should not happen in normal usage, but handle it for type safety
        return '';
    }
}
