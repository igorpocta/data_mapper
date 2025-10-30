<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Cache;

use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Attributes\MapDateTimeProperty;
use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionNamedType;

/**
 * Factory for building ClassMetadata from reflection
 * Uses cache to avoid repeated reflection calls
 */
class ClassMetadataFactory
{
    private CacheInterface $cache;

    public function __construct(?CacheInterface $cache = null)
    {
        $this->cache = $cache ?? new ArrayCache();
    }

    /**
     * Get or build metadata for a class
     *
     * @template T of object
     * @param class-string<T> $className
     * @return ClassMetadata
     */
    public function getMetadata(string $className): ClassMetadata
    {
        $cacheKey = 'class_metadata:' . $className;

        // Try to get from cache
        $cached = $this->cache->get($cacheKey);
        if ($cached instanceof ClassMetadata) {
            return $cached;
        }

        // Build from reflection
        $metadata = $this->buildMetadata($className);

        // Store in cache
        $this->cache->set($cacheKey, $metadata);

        return $metadata;
    }

    /**
     * Clear cache for specific class or all classes
     *
     * @param class-string|null $className
     */
    public function clearCache(?string $className = null): void
    {
        if ($className === null) {
            $this->cache->clear();
        } else {
            $this->cache->delete('class_metadata:' . $className);
        }
    }

    /**
     * Build metadata from reflection
     *
     * @template T of object
     * @param class-string<T> $className
     * @return ClassMetadata
     */
    private function buildMetadata(string $className): ClassMetadata
    {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        // Build properties metadata
        $properties = [];
        foreach ($reflection->getProperties() as $property) {
            $propertyMeta = $this->buildPropertyMetadata($property);
            $properties[$property->getName()] = $propertyMeta;
        }

        // Build constructor metadata
        $constructorMeta = null;
        if ($constructor && $constructor->getNumberOfParameters() > 0) {
            $parameters = [];
            foreach ($constructor->getParameters() as $parameter) {
                $paramMeta = $this->buildParameterMetadata($parameter);
                $parameters[$parameter->getName()] = $paramMeta;
            }
            $constructorMeta = new ConstructorMetadata($parameters);
        }

        return new ClassMetadata($className, $properties, $constructorMeta);
    }

    /**
     * Build metadata for a property
     */
    private function buildPropertyMetadata(ReflectionProperty $property): PropertyMetadata
    {
        $dateTimeAttrs = $property->getAttributes(MapDateTimeProperty::class);
        $propertyAttrs = $property->getAttributes(MapProperty::class);

        $jsonKey = $this->getJsonKey($property->getName(), $dateTimeAttrs, $propertyAttrs);
        $typeName = $this->getTypeName($property->getType(), $dateTimeAttrs, $propertyAttrs);
        $isNullable = $property->getType()?->allowsNull() ?? true;

        // Check if promoted (exists in constructor parameters)
        $isPromoted = $property->isPromoted();

        // Get all attributes as objects
        $attributes = [];
        foreach ($property->getAttributes() as $attr) {
            $attributes[] = $attr->newInstance();
        }

        $format = $this->getFormat($dateTimeAttrs);
        $timezone = $this->getTimezone($dateTimeAttrs);
        $arrayOf = $this->getArrayOf($dateTimeAttrs, $propertyAttrs);
        $classType = $this->getClassType($propertyAttrs);

        return new PropertyMetadata(
            $property->getName(),
            $jsonKey,
            $typeName,
            $isNullable,
            $isPromoted,
            $attributes,
            $format,
            $timezone,
            $arrayOf,
            $classType
        );
    }

    /**
     * Build metadata for a constructor parameter
     */
    private function buildParameterMetadata(ReflectionParameter $parameter): ParameterMetadata
    {
        $dateTimeAttrs = $parameter->getAttributes(MapDateTimeProperty::class);
        $propertyAttrs = $parameter->getAttributes(MapProperty::class);

        $jsonKey = $this->getJsonKey($parameter->getName(), $dateTimeAttrs, $propertyAttrs);
        $typeName = $this->getTypeName($parameter->getType(), $dateTimeAttrs, $propertyAttrs);
        $isNullable = $parameter->allowsNull();
        $hasDefaultValue = $parameter->isDefaultValueAvailable();
        $defaultValue = $hasDefaultValue ? $parameter->getDefaultValue() : null;

        // Get all attributes as objects
        $attributes = [];
        foreach ($parameter->getAttributes() as $attr) {
            $attributes[] = $attr->newInstance();
        }

        $format = $this->getFormat($dateTimeAttrs);
        $timezone = $this->getTimezone($dateTimeAttrs);
        $arrayOf = $this->getArrayOf($dateTimeAttrs, $propertyAttrs);
        $classType = $this->getClassType($propertyAttrs);

        return new ParameterMetadata(
            $parameter->getName(),
            $jsonKey,
            $typeName,
            $isNullable,
            $hasDefaultValue,
            $defaultValue,
            $attributes,
            $format,
            $timezone,
            $arrayOf,
            $classType
        );
    }

    /**
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttrs
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttrs
     */
    private function getJsonKey(string $defaultName, array $dateTimeAttrs, array $propertyAttrs): string
    {
        if (!empty($dateTimeAttrs)) {
            $attr = $dateTimeAttrs[0]->newInstance();
            return $attr->name ?? $defaultName;
        }
        if (!empty($propertyAttrs)) {
            $attr = $propertyAttrs[0]->newInstance();
            return $attr->name ?? $defaultName;
        }
        return $defaultName;
    }

    /**
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttrs
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttrs
     */
    private function getTypeName(
        ?\ReflectionType $type,
        array $dateTimeAttrs,
        array $propertyAttrs
    ): string {
        if (!empty($dateTimeAttrs)) {
            $attr = $dateTimeAttrs[0]->newInstance();
            if ($attr->type !== null) {
                return $attr->type->value;
            }
        }
        if (!empty($propertyAttrs)) {
            $attr = $propertyAttrs[0]->newInstance();
            if ($attr->type !== null) {
                return $attr->type->value;
            }
        }
        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }
        return 'string';
    }

    /**
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttrs
     */
    private function getFormat(array $dateTimeAttrs): ?string
    {
        if (empty($dateTimeAttrs)) {
            return null;
        }
        $attr = $dateTimeAttrs[0]->newInstance();
        return $attr->format;
    }

    /**
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttrs
     */
    private function getTimezone(array $dateTimeAttrs): ?string
    {
        if (empty($dateTimeAttrs)) {
            return null;
        }
        $attr = $dateTimeAttrs[0]->newInstance();
        return $attr->timezone;
    }

    /**
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttrs
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttrs
     * @return class-string|null
     */
    private function getArrayOf(array $dateTimeAttrs, array $propertyAttrs): ?string
    {
        if (!empty($dateTimeAttrs)) {
            $attr = $dateTimeAttrs[0]->newInstance();
            return $attr->arrayOf;
        }
        if (!empty($propertyAttrs)) {
            $attr = $propertyAttrs[0]->newInstance();
            return $attr->arrayOf;
        }
        return null;
    }

    /**
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttrs
     * @return class-string|null
     */
    private function getClassType(array $propertyAttrs): ?string
    {
        if (empty($propertyAttrs)) {
            return null;
        }
        $attr = $propertyAttrs[0]->newInstance();
        return $attr->classType;
    }
}
