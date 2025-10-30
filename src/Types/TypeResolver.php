<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Types;

use InvalidArgumentException;

class TypeResolver
{
    /** @var array<string, TypeInterface> */
    private array $types = [];

    /** @var array<string, string> */
    private array $aliasMap = [];

    public function __construct()
    {
        $this->registerDefaultTypes();
    }

    /**
     * Registers default types (int, string, bool)
     */
    private function registerDefaultTypes(): void
    {
        $this->registerType(new IntType());
        $this->registerType(new StringType());
        $this->registerType(new BoolType());
        $this->registerType(new FloatType());
    }

    /**
     * Registers a new type
     *
     * @param TypeInterface $type
     */
    public function registerType(TypeInterface $type): void
    {
        $canonicalName = $type->getName();
        $this->types[$canonicalName] = $type;

        // Register all aliases
        foreach ($type->getAliases() as $alias) {
            $this->aliasMap[$alias] = $canonicalName;
        }
    }

    /**
     * Gets a type by name or alias
     *
     * @param string $typeName
     * @param string|null $format Optional format (for DateTime types)
     * @param string|null $timezone Optional timezone (for DateTime types)
     * @param class-string|null $arrayOf Optional class name for array elements
     * @param class-string|null $classType Optional class name for nested objects
     * @return TypeInterface
     * @throws InvalidArgumentException If type is not found
     */
    public function getType(
        string $typeName,
        ?string $format = null,
        ?string $timezone = null,
        ?string $arrayOf = null,
        ?string $classType = null
    ): TypeInterface {
        // Check if it's an array type with objects
        if ($typeName === 'array' && $arrayOf !== null) {
            return $this->createArrayType($arrayOf);
        }

        // Check if it's a mixed array (no arrayOf specified)
        if ($typeName === 'array') {
            return $this->createMixedArrayType();
        }

        // Check if it's a DateTimeInterface type
        if ($this->isDateTimeType($typeName)) {
            /** @var class-string<\DateTimeInterface> $typeName */
            return $this->createDateTimeType($typeName, $format, $timezone);
        }

        // Check if it's an alias
        if (isset($this->aliasMap[$typeName])) {
            $canonicalName = $this->aliasMap[$typeName];
            return $this->types[$canonicalName];
        }

        // Check if it's a canonical name
        if (isset($this->types[$typeName])) {
            return $this->types[$typeName];
        }

        // Check if it's an enum class (before checking class_exists, because enums are also classes)
        if (enum_exists($typeName)) {
            return $this->createEnumType($typeName);
        }

        // Check if it's a class (nested object)
        if ($classType !== null && class_exists($classType)) {
            return $this->createObjectType($classType);
        }

        // Check if typeName is a class name (auto-detection for nested objects)
        if (class_exists($typeName)) {
            return $this->createObjectType($typeName);
        }

        throw new InvalidArgumentException(
            "Unsupported type '{$typeName}'. Available types: " . implode(', ', array_keys($this->aliasMap))
        );
    }

    /**
     * Creates and caches an enum type handler
     *
     * @param class-string<\UnitEnum> $enumClass
     * @return TypeInterface
     */
    private function createEnumType(string $enumClass): TypeInterface
    {
        // Determine if it's a BackedEnum or UnitEnum
        if (is_subclass_of($enumClass, \BackedEnum::class)) {
            /** @var class-string<\BackedEnum> $enumClass */
            $type = new BackedEnumType($enumClass);
        } else {
            $type = new UnitEnumType($enumClass);
        }

        // Cache the type
        $this->types[$enumClass] = $type;
        $this->aliasMap[$enumClass] = $enumClass;

        return $type;
    }

    /**
     * Checks if a type is registered
     *
     * @param string $typeName
     * @return bool
     */
    public function hasType(string $typeName): bool
    {
        return isset($this->aliasMap[$typeName]) || isset($this->types[$typeName]);
    }

    /**
     * Gets all registered types
     *
     * @return array<string, TypeInterface>
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Checks if a type is a DateTime-related type
     *
     * @param string $typeName
     * @return bool
     */
    private function isDateTimeType(string $typeName): bool
    {
        return in_array($typeName, [
            \DateTime::class,
            \DateTimeImmutable::class,
            \DateTimeInterface::class,
        ], true);
    }

    /**
     * Creates a DateTime type handler with optional format and timezone
     *
     * @param class-string $className
     * @param string|null $format
     * @param string|null $timezone
     * @return TypeInterface
     */
    /**
     * @param class-string<\DateTimeInterface> $className
     */
    private function createDateTimeType(string $className, ?string $format, ?string $timezone): TypeInterface
    {
        // Create a cache key based on class, format, and timezone
        $cacheKey = $className;
        if ($format !== null) {
            $cacheKey .= ':format:' . $format;
        }
        if ($timezone !== null) {
            $cacheKey .= ':tz:' . $timezone;
        }

        // Check if we already have this configuration cached
        if (isset($this->types[$cacheKey])) {
            return $this->types[$cacheKey];
        }

        // Create new DateTimeType instance
        $type = new DateTimeType($className, $format, $timezone);

        // Cache it
        $this->types[$cacheKey] = $type;

        return $type;
    }

    /**
     * Creates an array type handler for arrays of objects
     *
     * @param class-string $elementClassName
     * @return TypeInterface
     */
    private function createArrayType(string $elementClassName): TypeInterface
    {
        $cacheKey = 'array<' . $elementClassName . '>';

        // Check if we already have this configuration cached
        if (isset($this->types[$cacheKey])) {
            return $this->types[$cacheKey];
        }

        // Create new ArrayType instance
        $type = new ArrayType($elementClassName);

        // Cache it
        $this->types[$cacheKey] = $type;

        return $type;
    }

    /**
     * Creates a mixed array type handler (array without specific element type)
     *
     * @return TypeInterface
     */
    private function createMixedArrayType(): TypeInterface
    {
        $cacheKey = 'array<mixed>';

        // Check if we already have this configuration cached
        if (isset($this->types[$cacheKey])) {
            return $this->types[$cacheKey];
        }

        // Create new ArrayType instance without element class (mixed array)
        $type = new ArrayType(null, null);

        // Cache it
        $this->types[$cacheKey] = $type;

        return $type;
    }

    /**
     * Creates an object type handler for nested objects
     *
     * @param class-string $className
     * @return TypeInterface
     */
    private function createObjectType(string $className): TypeInterface
    {
        $cacheKey = 'object<' . $className . '>';

        // Check if we already have this configuration cached
        if (isset($this->types[$cacheKey])) {
            return $this->types[$cacheKey];
        }

        // Create new ObjectType instance
        $type = new ObjectType($className);

        // Cache it
        $this->types[$cacheKey] = $type;

        return $type;
    }
}
