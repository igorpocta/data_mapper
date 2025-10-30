<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Types;

interface TypeInterface
{
    /**
     * Denormalizes a value from raw data (e.g., from JSON)
     *
     * @param mixed $value The raw value to denormalize
     * @param string $fieldName The name of the field (for error messages)
     * @param bool $isNullable Whether the field accepts null values
     * @return mixed The denormalized value
     * @throws \InvalidArgumentException If the value cannot be denormalized
     */
    public function denormalize(mixed $value, string $fieldName, bool $isNullable): mixed;

    /**
     * Normalizes a value for serialization (e.g., to JSON)
     *
     * @param mixed $value The value to normalize
     * @return mixed The normalized value
     */
    public function normalize(mixed $value): mixed;

    /**
     * Returns the canonical name of this type
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns all supported type aliases
     *
     * @return array<string>
     */
    public function getAliases(): array;
}
