<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Types;

use InvalidArgumentException;

abstract class AbstractType implements TypeInterface
{
    public function denormalize(mixed $value, string $fieldName, bool $isNullable): mixed
    {
        if ($value === null) {
            if ($isNullable) {
                return null;
            }
            throw new InvalidArgumentException(
                "Field '{$fieldName}' does not accept null values"
            );
        }

        return $this->denormalizeValue($value, $fieldName);
    }

    public function normalize(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return $this->normalizeValue($value);
    }

    /**
     * Denormalizes a non-null value
     *
     * @param mixed $value
     * @param string $fieldName
     * @return mixed
     * @throws InvalidArgumentException
     */
    abstract protected function denormalizeValue(mixed $value, string $fieldName): mixed;

    /**
     * Normalizes a non-null value
     *
     * @param mixed $value
     * @return mixed
     */
    abstract protected function normalizeValue(mixed $value): mixed;
}
