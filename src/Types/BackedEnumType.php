<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Types;

use BackedEnum;
use InvalidArgumentException;

/**
 * Type handler for BackedEnum (enums with scalar values)
 *
 * @template T of BackedEnum
 */
class BackedEnumType implements TypeInterface
{
    /**
     * @param class-string<T> $enumClass
     */
    public function __construct(
        private readonly string $enumClass
    ) {
        if (!enum_exists($this->enumClass)) {
            throw new InvalidArgumentException(
                "Class '{$this->enumClass}' is not an enum"
            );
        }

        if (!is_subclass_of($this->enumClass, BackedEnum::class)) {
            throw new InvalidArgumentException(
                "Enum '{$this->enumClass}' must be a BackedEnum"
            );
        }
    }

    public function getName(): string
    {
        return $this->enumClass;
    }

    public function getAliases(): array
    {
        return [$this->enumClass];
    }

    /**
     * @param mixed $value The raw value to denormalize
     * @param string $fieldName The name of the field (for error messages)
     * @param bool $isNullable Whether the field accepts null values
     * @return T|null The enum instance
     */
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

        // If value is already an instance of the enum, return it
        if ($value instanceof $this->enumClass) {
            return $value;
        }

        // Ensure value is int or string for tryFrom
        if (!is_int($value) && !is_string($value)) {
            throw new InvalidArgumentException(
                "Field '{$fieldName}' must be an int or string for BackedEnum, got: " . get_debug_type($value)
            );
        }

        // Try to create enum from the value
        /** @var T|null $enum */
        $enum = ($this->enumClass)::tryFrom($value);

        if ($enum === null) {
            $validValues = array_map(
                fn(BackedEnum $case) => $case->value,
                ($this->enumClass)::cases()
            );
            $valueStr = is_string($value) ? $value : (string) $value;
            throw new InvalidArgumentException(
                "Invalid value for field '{$fieldName}'. Expected one of: "
                . implode(', ', $validValues)
                . ", got: {$valueStr}"
            );
        }

        return $enum;
    }

    /**
     * @param T|null $value The enum instance to normalize
     * @return int|string|null The scalar value
     */
    public function normalize(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        // @phpstan-ignore-next-line instanceof.alwaysTrue (runtime safety check)
        if (!$value instanceof BackedEnum) {
            throw new InvalidArgumentException(
                "Expected instance of BackedEnum, got: " . get_debug_type($value)
            );
        }

        return $value->value;
    }
}
