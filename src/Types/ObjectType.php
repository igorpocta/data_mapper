<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Types;

use InvalidArgumentException;
use Pocta\DataMapper\Denormalizer\Denormalizer;
use Pocta\DataMapper\Normalizer\Normalizer;
use Pocta\DataMapper\Exceptions\ValidationException;
use Pocta\DataMapper\Types\TypeResolver;

/**
 * Type handler for nested objects
 *
 * @template T of object
 */
class ObjectType implements TypeInterface
{
    private Denormalizer $denormalizer;
    private Normalizer $normalizer;

    /**
     * @param class-string<T> $className
     * @param bool $strictMode
     * @param TypeResolver|null $typeResolver
     */
    public function __construct(
        private readonly string $className,
        private readonly bool $strictMode = false,
        private readonly ?TypeResolver $typeResolver = null
    ) {
        if (!class_exists($this->className)) {
            throw new InvalidArgumentException(
                "Class '{$this->className}' does not exist"
            );
        }

        $this->denormalizer = new Denormalizer($this->typeResolver);
        $this->denormalizer->setStrictMode($this->strictMode);
        $this->normalizer = new Normalizer($this->typeResolver);
    }

    public function getName(): string
    {
        return $this->className;
    }

    public function getAliases(): array
    {
        return [$this->className];
    }

    /**
     * @param mixed $value The raw value to denormalize
     * @param string $fieldName The name of the field (for error messages)
     * @param bool $isNullable Whether the field accepts null values
     * @return T|null The denormalized object
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

        // If value is already an instance of the class, return it
        if ($value instanceof $this->className) {
            return $value;
        }

        // Value must be an array
        if (!is_array($value)) {
            throw new InvalidArgumentException(
                "Field '{$fieldName}' must be an array to be denormalized into {$this->className}, got: "
                . get_debug_type($value)
            );
        }

        // Denormalize the nested object with path prefix
        try {
            // Set path prefix for nested denormalization
            $this->denormalizer->setPathPrefix($fieldName);
            /** @var array<string, mixed> $value */
            $result = $this->denormalizer->denormalize($value, $this->className);
            // Reset path prefix after denormalization
            $this->denormalizer->setPathPrefix('');
            return $result;
        } catch (ValidationException $e) {
            // Reset path prefix in case of error
            $this->denormalizer->setPathPrefix('');
            // Errors already have full path from denormalizer, just re-throw
            throw $e;
        }
    }

    /**
     * @param T|null $value The object to normalize
     * @return array<string, mixed>|null The normalized array
     */
    public function normalize(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        // @phpstan-ignore-next-line function.alreadyNarrowedType (runtime safety check)
        if (!is_object($value)) {
            throw new InvalidArgumentException(
                "Expected object, got: " . get_debug_type($value)
            );
        }

        return $this->normalizer->normalize($value);
    }
}
