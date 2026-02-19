<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Normalizer;

use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Attributes\MapDateTimeProperty;
use Pocta\DataMapper\Types\TypeResolver;
use ReflectionClass;
use ReflectionProperty;
use ReflectionNamedType;

class Normalizer
{
    private TypeResolver $typeResolver;

    /**
     * Stack of objects currently being normalized (for cycle detection).
     *
     * @var array<int, int>
     */
    private array $objectStack = [];

    public function __construct(
        ?TypeResolver $typeResolver = null,
        /** @phpstan-ignore-next-line property.onlyWritten */
        private ?\Pocta\DataMapper\Cache\ClassMetadataFactory $metadataFactory = null
    ) {
        $this->typeResolver = $typeResolver ?? new TypeResolver();
    }

    /**
     * Converts an object to an associative array.
     *
     * @param object $object
     *
     * @return array<string, mixed>
     *
     * @throws \Pocta\DataMapper\Exceptions\CircularReferenceException
     */
    public function normalize(object $object): array
    {
        // Detect circular references
        $objectHash = spl_object_id($object);
        if (in_array($objectHash, $this->objectStack, true)) {
            throw new \Pocta\DataMapper\Exceptions\CircularReferenceException(
                sprintf(
                    'Circular reference detected for object of class "%s"',
                    get_class($object)
                )
            );
        }

        // Add to stack
        $this->objectStack[] = $objectHash;

        try {
            $reflection = new ReflectionClass($object);
            $data = [];

            foreach ($reflection->getProperties() as $property) {
                $jsonKey = $this->getJsonKey($property);
                $value = $this->getPropertyValue($property, $object);

                if ($value !== null || $this->shouldIncludeNull($property)) {
                    $typeName = $this->getPropertyType($property);
                    $arrayOf = $this->getArrayOf($property);
                    $classType = $this->getClassType($property);
                    $outputFormat = $this->getOutputFormat($property);
                    $normalizedValue = $this->normalizeValue($value, $typeName, $arrayOf, $classType, $outputFormat);
                    // Apply post-normalization filters, if any
                    $normalizedValue = $this->applyFilters($property, $normalizedValue);
                    $data[$jsonKey] = $normalizedValue;
                }
            }

            return $data;
        } finally {
            // Remove from stack
            array_pop($this->objectStack);
        }
    }

    /**
     * Gets the JSON key for a property.
     *
     * @param ReflectionProperty $property
     *
     * @return string
     */
    private function getJsonKey(ReflectionProperty $property): string
    {
        // Check MapDateTimeProperty first
        $dateTimeAttributes = $property->getAttributes(MapDateTimeProperty::class);
        if (!empty($dateTimeAttributes)) {
            $attr = $dateTimeAttributes[0]->newInstance();
            return $attr->name ?? $property->getName();
        }

        // Then check MapProperty
        $propertyAttributes = $property->getAttributes(MapProperty::class);
        if (!empty($propertyAttributes)) {
            $attr = $propertyAttributes[0]->newInstance();
            return $attr->name ?? $property->getName();
        }

        return $property->getName();
    }

    /**
     * Gets the value of a property.
     *
     * @param ReflectionProperty $property
     * @param object $object
     *
     * @return mixed
     */
    private function getPropertyValue(ReflectionProperty $property, object $object): mixed
    {
        $property->setAccessible(true);

        // Check if property is initialized to avoid accessing uninitialized typed properties
        if (!$property->isInitialized($object)) {
            return null;
        }

        return $property->getValue($object);
    }

    /**
     * Gets the type name for a property.
     *
     * @param ReflectionProperty $property
     *
     * @return string
     */
    private function getPropertyType(ReflectionProperty $property): string
    {
        // Check MapDateTimeProperty first
        $dateTimeAttributes = $property->getAttributes(MapDateTimeProperty::class);
        if (!empty($dateTimeAttributes)) {
            $attr = $dateTimeAttributes[0]->newInstance();
            if ($attr->type !== null) {
                return $attr->type->value;
            }
        }

        // Then check MapProperty
        $propertyAttributes = $property->getAttributes(MapProperty::class);
        if (!empty($propertyAttributes)) {
            $attr = $propertyAttributes[0]->newInstance();
            if ($attr->type !== null) {
                return $attr->type->value;
            }
        }

        $type = $property->getType();
        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        return 'string';
    }

    /**
     * Normalizes a value using the appropriate type handler.
     *
     * @param mixed $value
     * @param string $typeName
     * @param string|null $arrayOf Class name or scalar type name
     * @param class-string|null $classType
     * @param string|null $outputFormat Custom output format (for DateTime types)
     *
     * @return mixed
     */
    private function normalizeValue(mixed $value, string $typeName, ?string $arrayOf = null, ?string $classType = null, ?string $outputFormat = null): mixed
    {
        if ($value === null) {
            return null;
        }

        $type = $this->typeResolver->getType($typeName, null, null, $arrayOf, $classType, $outputFormat);
        return $type->normalize($value);
    }

    /**
     * Determines if null values should be included in output.
     *
     * @param ReflectionProperty $property
     *
     * @return bool
     */
    private function shouldIncludeNull(ReflectionProperty $property): bool
    {
        // For now, we skip null values. This can be configurable later
        return false;
    }

    /**
     * Gets arrayOf from property attributes.
     *
     * @param ReflectionProperty $property
     *
     * @return class-string|string|null
     */
    private function getArrayOf(ReflectionProperty $property): ?string
    {
        // Check MapDateTimeProperty first
        $dateTimeAttributes = $property->getAttributes(MapDateTimeProperty::class);
        if (!empty($dateTimeAttributes)) {
            $attr = $dateTimeAttributes[0]->newInstance();
            // Convert PropertyType or ArrayElementType enum to string value if needed
            if ($attr->arrayOf instanceof \Pocta\DataMapper\Attributes\PropertyType) {
                return $attr->arrayOf->value;
            }
            if ($attr->arrayOf instanceof \Pocta\DataMapper\Attributes\ArrayElementType) {
                return $attr->arrayOf->value;
            }
            return $attr->arrayOf;
        }

        // Then check MapProperty
        $propertyAttributes = $property->getAttributes(MapProperty::class);
        if (!empty($propertyAttributes)) {
            $attr = $propertyAttributes[0]->newInstance();
            // Convert PropertyType or ArrayElementType enum to string value if needed
            if ($attr->arrayOf instanceof \Pocta\DataMapper\Attributes\PropertyType) {
                return $attr->arrayOf->value;
            }
            if ($attr->arrayOf instanceof \Pocta\DataMapper\Attributes\ArrayElementType) {
                return $attr->arrayOf->value;
            }
            return $attr->arrayOf;
        }

        return null;
    }

    /**
     * Gets the output format for DateTime normalization from MapDateTimeProperty attribute.
     *
     * @param ReflectionProperty $property
     *
     * @return string|null
     */
    private function getOutputFormat(ReflectionProperty $property): ?string
    {
        $dateTimeAttributes = $property->getAttributes(MapDateTimeProperty::class);
        if (!empty($dateTimeAttributes)) {
            $attr = $dateTimeAttributes[0]->newInstance();
            return $attr->outputFormat;
        }

        return null;
    }

    /**
     * Gets classType from property attributes.
     *
     * @param ReflectionProperty $property
     *
     * @return class-string|null
     */
    private function getClassType(ReflectionProperty $property): ?string
    {
        // Only MapProperty has classType
        $propertyAttributes = $property->getAttributes(MapProperty::class);
        if (!empty($propertyAttributes)) {
            $attr = $propertyAttributes[0]->newInstance();
            return $attr->classType;
        }

        return null;
    }

    /**
     * Applies all filter attributes present on the property to the normalized value.
     * Filters are applied in the order they are declared on the property.
     *
     * @param ReflectionProperty $property
     * @param mixed $value
     *
     * @return mixed
     */
    private function applyFilters(ReflectionProperty $property, mixed $value): mixed
    {
        $attributes = $property->getAttributes();

        foreach ($attributes as $attribute) {
            // Instantiate and check if attribute is a filter
            $instance = $attribute->newInstance();
            if ($instance instanceof \Pocta\DataMapper\Attributes\Filters\FilterInterface) {
                $value = $instance->apply($value);
            }
        }

        return $value;
    }
}
