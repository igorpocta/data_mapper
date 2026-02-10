<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Denormalizer;

use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Attributes\MapDateTimeProperty;
use Pocta\DataMapper\Attributes\DiscriminatorMap;
use Pocta\DataMapper\Exceptions\ValidationException;
use Pocta\DataMapper\Types\TypeResolver;
use Pocta\DataMapper\PropertyPathResolver;
use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionNamedType;
use InvalidArgumentException;

class Denormalizer
{
    private TypeResolver $typeResolver;

    /** @var array<string, string> */
    private array $errors = [];

    private bool $strictMode = false;

    private bool $throwOnMissingData = true;

    /**
     * Context stack of payloads for nested denormalization levels.
     * Bottom (index 0) = root payload; Top (end) = current payload.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $contextStack = [];

    /** Whether this instance owns the root payload in TypeResolver. */
    private bool $ownsRootPayload = false;

    /**
     * Stack of class names with depths for cycle detection.
     * Format: [className => depth] where depth is the nesting level.
     *
     * @var array<string, int>
     */
    private array $classDepthMap = [];

    /**
     * Path prefix for nested denormalization (e.g., "addresses[0]" when denormalizing array element).
     * Used to provide better error messages with full path.
     */
    private string $pathPrefix = '';

    /**
     * Set the path prefix for error messages.
     *
     * @param string $prefix
     */
    public function setPathPrefix(string $prefix): void
    {
        $this->pathPrefix = $prefix;
    }

    /**
     * Get the path prefix.
     *
     * @return string
     */
    public function getPathPrefix(): string
    {
        return $this->pathPrefix;
    }

    /**
     * Build full field path for error messages.
     *
     * @param string $fieldName
     *
     * @return string
     */
    private function buildFullFieldPath(string $fieldName): string
    {
        if ($this->pathPrefix === '') {
            return $fieldName;
        }
        return $this->pathPrefix . '.' . $fieldName;
    }

    public function __construct(
        ?TypeResolver $typeResolver = null,
        /** @phpstan-ignore-next-line property.onlyWritten */
        private ?\Pocta\DataMapper\Cache\ClassMetadataFactory $metadataFactory = null
    ) {
        $this->typeResolver = $typeResolver ?? new TypeResolver();
    }

    /**
     * Enable or disable strict mode.
     */
    public function setStrictMode(bool $strictMode): void
    {
        $this->strictMode = $strictMode;
    }

    /**
     * Check if strict mode is enabled.
     */
    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }

    /**
     * Enable or disable throwing on missing data.
     */
    public function setThrowOnMissingData(bool $throwOnMissingData): void
    {
        $this->throwOnMissingData = $throwOnMissingData;
    }

    /**
     * Check if throwing on missing data is enabled.
     */
    public function isThrowOnMissingData(): bool
    {
        return $this->throwOnMissingData;
    }

    /**
     * Converts an associative array to an object.
     *
     * @template T of object
     *
     * @param array<string, mixed> $data
     * @param class-string<T> $className
     *
     * @return T
     *
     * @throws ValidationException
     * @throws \Pocta\DataMapper\Exceptions\CircularReferenceException
     */
    public function denormalize(array $data, string $className): object
    {
        // Reset errors only at the outermost call
        if (empty($this->contextStack)) {
            $this->errors = [];
        }

        // Initialize root payload in TypeResolver if not set
        if ($this->typeResolver->getRootPayload() === null) {
            $this->typeResolver->setRootPayload($data);
            $this->ownsRootPayload = true;
        }

        // Push current payload FIRST to get correct depth
        $this->contextStack[] = $data;

        // Get current depth (how many denormalize calls deep we are)
        $currentDepth = count($this->contextStack);

        // Detect circular references: if this class is already being processed at a SHALLOWER depth,
        // it's a cycle. If it's at the SAME depth, it's a sibling (e.g., array elements) which is OK.
        if (isset($this->classDepthMap[$className]) && $this->classDepthMap[$className] < $currentDepth) {
            $stack = array_keys($this->classDepthMap);
            throw new \Pocta\DataMapper\Exceptions\CircularReferenceException(
                sprintf(
                    'Circular reference detected: attempting to denormalize "%s" which is already being processed in the call stack: %s',
                    $className,
                    implode(' -> ', array_merge($stack, [$className]))
                )
            );
        }

        // Record this class at current depth
        $this->classDepthMap[$className] = $currentDepth;

        try {
            // Resolve the actual class to instantiate (handles discriminator mapping)
            $className = $this->resolveClassName($className, $data);

            $reflection = new ReflectionClass($className);
            $constructor = $reflection->getConstructor();

            // Validate unknown keys in strict mode
            if ($this->strictMode) {
                $this->validateUnknownKeys($reflection, $constructor, $data);
            }

            if ($constructor && $constructor->getNumberOfParameters() > 0) {
                $result = $this->createWithConstructor($reflection, $constructor, $data);
            } else {
                $result = $this->createWithoutConstructor($reflection, $data);
            }

            // Check if any errors were collected
            if (!empty($this->errors)) {
                throw new ValidationException($this->errors);
            }

            if (empty($this->contextStack) && $this->ownsRootPayload) {
                $this->typeResolver->setRootPayload(null);
                $this->ownsRootPayload = false;
            }
            return $result;
        } finally {
            // Always clean up stack and depth map
            array_pop($this->contextStack);
            unset($this->classDepthMap[$className]);
        }
    }

    /**
     * Creates an object using constructor.
     *
     * @template T of object
     *
     * @param ReflectionClass<T> $reflection
     * @param \ReflectionMethod $constructor
     * @param array<string, mixed> $data
     *
     * @return T
     *
     * @throws ValidationException
     */
    private function createWithConstructor(
        ReflectionClass $reflection,
        \ReflectionMethod $constructor,
        array $data
    ): object {
        $constructorArgs = [];

        foreach ($constructor->getParameters() as $parameter) {
            $value = $this->getParameterValue($parameter, $data);
            $constructorArgs[] = $value;
        }

        // If there were errors with constructor parameters, throw immediately
        // because we can't create the instance
        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }

        $instance = $reflection->newInstanceArgs($constructorArgs);

        // Set remaining properties that are not in constructor
        $this->setNonConstructorProperties($reflection, $instance, $data, $constructor->getParameters());

        return $instance;
    }

    /**
     * Creates an object without using constructor.
     *
     * @template T of object
     *
     * @param ReflectionClass<T> $reflection
     * @param array<string, mixed> $data
     *
     * @return T
     */
    private function createWithoutConstructor(ReflectionClass $reflection, array $data): object
    {
        $instance = $reflection->newInstanceWithoutConstructor();

        foreach ($reflection->getProperties() as $property) {
            $this->setPropertyValue($property, $instance, $data);
        }

        return $instance;
    }

    /**
     * Gets value for a constructor parameter.
     *
     * @param ReflectionParameter $parameter
     * @param array<string, mixed> $data
     *
     * @return mixed
     */
    private function getParameterValue(ReflectionParameter $parameter, array $data): mixed
    {
        // Check for MapDateTimeProperty first, then MapProperty
        $dateTimeAttributes = $parameter->getAttributes(MapDateTimeProperty::class);
        $propertyAttributes = $parameter->getAttributes(MapProperty::class);

        $jsonKey = $this->getJsonKeyFromParameter($parameter, $dateTimeAttributes, $propertyAttributes);
        $path = $this->getPathFromParameter($dateTimeAttributes, $propertyAttributes);

        // Use PropertyPathResolver if path is specified
        if ($path !== null) {
            try {
                $hasKey = PropertyPathResolver::exists($data, $path);
                $value = $hasKey ? PropertyPathResolver::resolve($data, $path) : null;
            } catch (InvalidArgumentException $e) {
                // Invalid path syntax
                $fullPath = $this->buildFullFieldPath($path);
                $this->errors[$fullPath] = "Invalid property path syntax for parameter '{$parameter->getName()}': {$e->getMessage()}";
                return null;
            }
        } else {
            $hasKey = array_key_exists($jsonKey, $data);
            $value = $hasKey ? $data[$jsonKey] : null;
        }

        if (!$hasKey) {
            // If MapPropertyWithFunction present, compute value even when key is missing
            $hasHydrator = !empty($parameter->getAttributes(\Pocta\DataMapper\Attributes\MapPropertyWithFunction::class));
            if (!$hasHydrator) {
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }
                if ($parameter->allowsNull()) {
                    return null;
                }
                // Only add error if throwOnMissingData is enabled
                if ($this->throwOnMissingData) {
                    $fullPath = $this->buildFullFieldPath($path ?? $jsonKey);
                    if ($path !== null) {
                        // Provide more context for path resolution failures
                        $this->errors[$fullPath] = $this->buildPathResolutionError($parameter->getName(), $path, $data, $fullPath);
                    } else {
                        $this->errors[$fullPath] = "Missing required parameter '{$parameter->getName()}' at path '{$fullPath}'";
                    }
                }
                return null;
            }
        }

        // Apply hydrator and filters BEFORE type denormalization
        $value = $this->applyHydratorToParameter($parameter, $value, $path ?? $jsonKey);
        $value = $this->applyFiltersToParameter($parameter, $value);
        $typeName = $this->getParameterTypeFromAttributes($parameter, $dateTimeAttributes, $propertyAttributes);
        $format = $this->getFormatFromParameter($dateTimeAttributes);
        $timezone = $this->getTimezoneFromParameter($dateTimeAttributes);
        $arrayOf = $this->getArrayOfFromParameter($dateTimeAttributes, $propertyAttributes);
        $classType = $this->getClassTypeFromParameter($propertyAttributes);

        $fullPath = $this->buildFullFieldPath($path ?? $jsonKey);
        $typed = $this->denormalizeValue($value, $typeName, $fullPath, $parameter->allowsNull(), $format, $timezone, $arrayOf, $classType);
        // Apply filters AFTER type denormalization
        $typed = $this->applyFiltersToParameter($parameter, $typed);
        return $typed;
    }

    /**
     * Sets value for a property.
     *
     * @param ReflectionProperty $property
     * @param object $instance
     * @param array<string, mixed> $data
     */
    private function setPropertyValue(ReflectionProperty $property, object $instance, array $data): void
    {
        // Check for MapDateTimeProperty first, then MapProperty
        $dateTimeAttributes = $property->getAttributes(MapDateTimeProperty::class);
        $propertyAttributes = $property->getAttributes(MapProperty::class);

        $jsonKey = $this->getJsonKeyFromProperty($property, $dateTimeAttributes, $propertyAttributes);
        $path = $this->getPathFromProperty($dateTimeAttributes, $propertyAttributes);

        // Use PropertyPathResolver if path is specified
        if ($path !== null) {
            try {
                $hasKey = PropertyPathResolver::exists($data, $path);
                $value = $hasKey ? PropertyPathResolver::resolve($data, $path) : null;
            } catch (InvalidArgumentException $e) {
                // Invalid path syntax
                $fullPath = $this->buildFullFieldPath($path);
                $this->errors[$fullPath] = "Invalid property path syntax for property '{$property->getName()}': {$e->getMessage()}";
                return;
            }
        } else {
            $hasKey = array_key_exists($jsonKey, $data);
            $value = $hasKey ? $data[$jsonKey] : null;
        }

        if (!$hasKey) {
            // If MapPropertyWithFunction present, compute value even when key is missing
            $hasHydrator = !empty($property->getAttributes(\Pocta\DataMapper\Attributes\MapPropertyWithFunction::class));
            if (!$hasHydrator) {
                // For properties (not in constructor), missing data is not an error unless required
                // Check if property is required (not nullable and no default value)
                $isNullable = $this->isPropertyNullable($property);
                $hasDefault = $property->hasDefaultValue();
                if (!$isNullable && !$hasDefault && $this->throwOnMissingData) {
                    $fullPath = $this->buildFullFieldPath($path ?? $jsonKey);
                    $this->errors[$fullPath] = "Missing required property '{$property->getName()}' at path '{$fullPath}'";
                }
                return;
            }
        }

        // Apply hydrator and filters BEFORE type denormalization
        $value = $this->applyHydratorToProperty($property, $value, $path ?? $jsonKey);
        $value = $this->applyFiltersToProperty($property, $value);
        $typeName = $this->getPropertyTypeFromAttributes($property, $dateTimeAttributes, $propertyAttributes);
        $isNullable = $this->isPropertyNullable($property);
        $format = $this->getFormatFromProperty($dateTimeAttributes);
        $timezone = $this->getTimezoneFromProperty($dateTimeAttributes);
        $arrayOf = $this->getArrayOfFromProperty($dateTimeAttributes, $propertyAttributes);
        $classType = $this->getClassTypeFromProperty($propertyAttributes);

        $fullPath = $this->buildFullFieldPath($path ?? $jsonKey);
        $errorCountBefore = count($this->errors);
        $typedValue = $this->denormalizeValue($value, $typeName, $fullPath, $isNullable, $format, $timezone, $arrayOf, $classType);
        // Apply filters AFTER type denormalization
        $typedValue = $this->applyFiltersToProperty($property, $typedValue);

        // Don't set the value if there were any errors during denormalization
        // (including nested errors stored under deeper paths like "child.status")
        if (count($this->errors) > $errorCountBefore) {
            return;
        }

        $property->setAccessible(true);
        $property->setValue($instance, $typedValue);
    }

    /**
     * Sets properties that are not in constructor.
     *
     * @param ReflectionClass<object> $reflection
     * @param object $instance
     * @param array<string, mixed> $data
     * @param array<int, ReflectionParameter> $constructorParams
     */
    private function setNonConstructorProperties(
        ReflectionClass $reflection,
        object $instance,
        array $data,
        array $constructorParams
    ): void {
        $constructorParamNames = array_map(
            fn(ReflectionParameter $param) => $param->getName(),
            $constructorParams
        );

        foreach ($reflection->getProperties() as $property) {
            // Skip if property is promoted (already set via constructor)
            if (in_array($property->getName(), $constructorParamNames, true)) {
                continue;
            }

            $this->setPropertyValue($property, $instance, $data);
        }
    }

    /**
     * Gets JSON key from parameter attributes.
     *
     * @param ReflectionParameter $parameter
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttributes
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttributes
     * @return string
     */
    private function getJsonKeyFromParameter(
        ReflectionParameter $parameter,
        array $dateTimeAttributes,
        array $propertyAttributes
    ): string {
        // MapDateTimeProperty has priority
        if (!empty($dateTimeAttributes)) {
            $attr = $dateTimeAttributes[0]->newInstance();
            return $attr->name ?? $parameter->getName();
        }

        if (!empty($propertyAttributes)) {
            $attr = $propertyAttributes[0]->newInstance();
            return $attr->name ?? $parameter->getName();
        }

        return $parameter->getName();
    }

    /**
     * Gets JSON key from property attributes.
     *
     * @param ReflectionProperty $property
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttributes
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttributes
     * @return string
     */
    private function getJsonKeyFromProperty(
        ReflectionProperty $property,
        array $dateTimeAttributes,
        array $propertyAttributes
    ): string {
        // MapDateTimeProperty has priority
        if (!empty($dateTimeAttributes)) {
            $attr = $dateTimeAttributes[0]->newInstance();
            return $attr->name ?? $property->getName();
        }

        if (!empty($propertyAttributes)) {
            $attr = $propertyAttributes[0]->newInstance();
            return $attr->name ?? $property->getName();
        }

        return $property->getName();
    }

    /**
     * Gets type from parameter attributes.
     *
     * @param ReflectionParameter $parameter
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttributes
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttributes
     * @return string
     */
    private function getParameterTypeFromAttributes(
        ReflectionParameter $parameter,
        array $dateTimeAttributes,
        array $propertyAttributes
    ): string {
        // MapDateTimeProperty has priority
        if (!empty($dateTimeAttributes)) {
            $attr = $dateTimeAttributes[0]->newInstance();
            if ($attr->type !== null) {
                return $attr->type->value;
            }
        }

        if (!empty($propertyAttributes)) {
            $attr = $propertyAttributes[0]->newInstance();
            if ($attr->type !== null) {
                return $attr->type->value;
            }
        }

        $type = $parameter->getType();
        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        return 'string';
    }

    /**
     * Gets type from property attributes.
     *
     * @param ReflectionProperty $property
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttributes
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttributes
     * @return string
     */
    private function getPropertyTypeFromAttributes(
        ReflectionProperty $property,
        array $dateTimeAttributes,
        array $propertyAttributes
    ): string {
        // MapDateTimeProperty has priority
        if (!empty($dateTimeAttributes)) {
            $attr = $dateTimeAttributes[0]->newInstance();
            if ($attr->type !== null) {
                return $attr->type->value;
            }
        }

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
     * Checks if property is nullable.
     *
     * @param ReflectionProperty $property
     *
     * @return bool
     */
    private function isPropertyNullable(ReflectionProperty $property): bool
    {
        $type = $property->getType();
        return $type === null || $type->allowsNull();
    }

    /**
     * Denormalizes a value using the appropriate type handler.
     *
     * @param mixed $value
     * @param string $typeName
     * @param string $fieldName
     * @param bool $isNullable
     * @param string|null $format
     * @param string|null $timezone
     * @param string|null $arrayOf Class name or scalar type name
     * @param class-string|null $classType
     *
     * @return mixed
     */
    private function denormalizeValue(
        mixed $value,
        string $typeName,
        string $fieldName,
        bool $isNullable,
        ?string $format = null,
        ?string $timezone = null,
        ?string $arrayOf = null,
        ?string $classType = null
    ): mixed {
        try {
            $type = $this->typeResolver->getType($typeName, $format, $timezone, $arrayOf, $classType);
            return $type->denormalize($value, $fieldName, $isNullable);
        } catch (ValidationException $e) {
            // Merge errors from nested validation (e.g., arrayOf validation)
            foreach ($e->getErrors() as $errorKey => $errorMessage) {
                $this->errors[$errorKey] = $errorMessage;
            }
            // Return null or default value based on nullability
            return null;
        } catch (InvalidArgumentException $e) {
            // Collect error instead of throwing immediately
            $this->errors[$fieldName] = $e->getMessage();
            // Return null or default value based on nullability
            return null;
        }
    }

    /**
     * Gets format from parameter attributes.
     *
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttributes
     * @return string|null
     */
    private function getFormatFromParameter(array $dateTimeAttributes): ?string
    {
        if (empty($dateTimeAttributes)) {
            return null;
        }

        $attr = $dateTimeAttributes[0]->newInstance();
        return $attr->format;
    }

    /**
     * Gets timezone from parameter attributes.
     *
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttributes
     * @return string|null
     */
    private function getTimezoneFromParameter(array $dateTimeAttributes): ?string
    {
        if (empty($dateTimeAttributes)) {
            return null;
        }

        $attr = $dateTimeAttributes[0]->newInstance();
        return $attr->timezone;
    }

    /**
     * Gets format from property attributes.
     *
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttributes
     * @return string|null
     */
    private function getFormatFromProperty(array $dateTimeAttributes): ?string
    {
        if (empty($dateTimeAttributes)) {
            return null;
        }

        $attr = $dateTimeAttributes[0]->newInstance();
        return $attr->format;
    }

    /**
     * Gets timezone from property attributes.
     *
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttributes
     * @return string|null
     */
    private function getTimezoneFromProperty(array $dateTimeAttributes): ?string
    {
        if (empty($dateTimeAttributes)) {
            return null;
        }

        $attr = $dateTimeAttributes[0]->newInstance();
        return $attr->timezone;
    }

    /**
     * Gets arrayOf from parameter attributes.
     *
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttributes
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttributes
     * @return class-string|string|null
     */
    private function getArrayOfFromParameter(array $dateTimeAttributes, array $propertyAttributes): ?string
    {
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
     * Gets arrayOf from property attributes.
     *
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttributes
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttributes
     * @return class-string|string|null
     */
    private function getArrayOfFromProperty(array $dateTimeAttributes, array $propertyAttributes): ?string
    {
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
     * Gets classType from parameter attributes.
     *
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttributes
     * @return class-string|null
     */
    private function getClassTypeFromParameter(array $propertyAttributes): ?string
    {
        if (empty($propertyAttributes)) {
            return null;
        }

        $attr = $propertyAttributes[0]->newInstance();
        return $attr->classType;
    }

    /**
     * Gets classType from property attributes.
     *
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttributes
     * @return class-string|null
     */
    private function getClassTypeFromProperty(array $propertyAttributes): ?string
    {
        if (empty($propertyAttributes)) {
            return null;
        }

        $attr = $propertyAttributes[0]->newInstance();
        return $attr->classType;
    }

    /**
     * Applies all filter attributes present on the parameter to the incoming raw value.
     * Filters are applied in the order they are declared on the parameter.
     *
     * @param ReflectionParameter $parameter
     * @param mixed $value
     * @return mixed
     */
    private function applyFiltersToParameter(ReflectionParameter $parameter, mixed $value): mixed
    {
        foreach ($parameter->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof \Pocta\DataMapper\Attributes\Filters\FilterInterface) {
                $value = $instance->apply($value);
            }
        }
        return $value;
    }

    /**
     * Applies all filter attributes present on the property to the incoming raw value.
     * Filters are applied in the order they are declared on the property.
     *
     * @param ReflectionProperty $property
     * @param mixed $value
     * @return mixed
     */
    private function applyFiltersToProperty(ReflectionProperty $property, mixed $value): mixed
    {
        foreach ($property->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof \Pocta\DataMapper\Attributes\Filters\FilterInterface) {
                $value = $instance->apply($value);
            }
        }
        return $value;
    }

    /** Applies MapPropertyWithFunction attribute for parameter (pre-denormalization), if present. */
    private function applyHydratorToParameter(ReflectionParameter $parameter, mixed $value, string $jsonKey): mixed
    {
        $attrs = $parameter->getAttributes(\Pocta\DataMapper\Attributes\MapPropertyWithFunction::class);
        if (empty($attrs)) {
            return $value;
        }
        /** @var \Pocta\DataMapper\Attributes\MapPropertyWithFunction $cfg */
        $cfg = $attrs[0]->newInstance();
        return $this->invokeHydrator($cfg, $value, $jsonKey);
    }

    /** Applies MapPropertyWithFunction attribute for property (pre-denormalization), if present. */
    private function applyHydratorToProperty(ReflectionProperty $property, mixed $value, string $jsonKey): mixed
    {
        $attrs = $property->getAttributes(\Pocta\DataMapper\Attributes\MapPropertyWithFunction::class);
        if (empty($attrs)) {
            return $value;
        }
        /** @var \Pocta\DataMapper\Attributes\MapPropertyWithFunction $cfg */
        $cfg = $attrs[0]->newInstance();
        return $this->invokeHydrator($cfg, $value, $jsonKey);
    }

    /** Invokes configured hydrator function with payload selected by mode. */
    private function invokeHydrator(\Pocta\DataMapper\Attributes\MapPropertyWithFunction $cfg, mixed $value, string $fieldName): mixed
    {
        $callable = $cfg->function;
        if (!is_callable($callable)) {
            // @phpstan-ignore-next-line function.alreadyNarrowedType
            $callableStr = is_array($callable) ? implode('::', $callable) : (string) $callable;
            $fullPath = $this->buildFullFieldPath($fieldName);
            $this->errors[$fullPath] = "Hydrator function '{$callableStr}' is not callable at path '{$fullPath}'";
            return $value;
        }

        $payload = match ($cfg->mode) {
            \Pocta\DataMapper\Attributes\HydrationMode::VALUE => $value,
            \Pocta\DataMapper\Attributes\HydrationMode::PARENT => $this->getCurrentPayload(),
            \Pocta\DataMapper\Attributes\HydrationMode::FULL => $this->getRootPayload(),
        };

        try {
            return \call_user_func($callable, $payload);
        } catch (\Throwable $e) {
            $fullPath = $this->buildFullFieldPath($fieldName);
            $this->errors[$fullPath] = "Hydrator function error at path '{$fullPath}': " . $e->getMessage();
            return $value;
        }
    }

    /**
     * Returns current (top) payload or empty array.
     * @return array<string, mixed>
     */
    private function getCurrentPayload(): array
    {
        return $this->contextStack[count($this->contextStack) - 1] ?? [];
    }

    /**
     * Returns root (bottom) payload or current if stack is empty.
     *
     * @return array<string, mixed>
     */
    private function getRootPayload(): array
    {
        // Try to get root payload from TypeResolver first (shared across instances)
        $rootPayload = $this->typeResolver->getRootPayload();
        if ($rootPayload !== null) {
            return $rootPayload;
        }
        // Fallback to context stack
        return $this->contextStack[0] ?? ($this->contextStack[count($this->contextStack) - 1] ?? []);
    }

    /**
     * Resolves the actual class name to instantiate based on discriminator mapping.
     * If no discriminator is present, returns the original class name.
     *
     * @template T of object
     * @param class-string<T> $className
     * @param array<string, mixed> $data
     * @return class-string<T>
     * @throws ValidationException
     */
    private function resolveClassName(string $className, array $data): string
    {
        $reflection = new ReflectionClass($className);
        $discriminatorAttributes = $reflection->getAttributes(DiscriminatorMap::class);

        if (empty($discriminatorAttributes)) {
            return $className;
        }

        /** @var DiscriminatorMap $discriminator */
        $discriminator = $discriminatorAttributes[0]->newInstance();

        // Check if discriminator property exists in data
        if (!array_key_exists($discriminator->property, $data)) {
            if ($this->throwOnMissingData) {
                $fullPath = $this->buildFullFieldPath($discriminator->property);
                $this->errors[$fullPath] = "Missing discriminator property '{$discriminator->property}' at path '{$fullPath}'";
                throw new ValidationException($this->errors);
            }
            // If not throwing, return the base class name (fallback)
            return $className;
        }

        $discriminatorValue = $data[$discriminator->property];

        // Ensure discriminator value is a string (for array key access)
        if (!is_string($discriminatorValue) && !is_int($discriminatorValue)) {
            $fullPath = $this->buildFullFieldPath($discriminator->property);
            $type = get_debug_type($discriminatorValue);
            $this->errors[$fullPath] = "Discriminator value must be string or int, {$type} given at path '{$fullPath}'";
            throw new ValidationException($this->errors);
        }

        // Convert to string for consistent array key access
        $discriminatorKey = (string) $discriminatorValue;

        // Check if discriminator value is mapped to a class
        if (!isset($discriminator->mapping[$discriminatorKey])) {
            $fullPath = $this->buildFullFieldPath($discriminator->property);
            $availableValues = implode(', ', array_keys($discriminator->mapping));
            $this->errors[$fullPath] = "Unknown discriminator value '{$discriminatorKey}' at path '{$fullPath}'. Available values: {$availableValues}";
            throw new ValidationException($this->errors);
        }

        /** @var class-string<T> */
        return $discriminator->mapping[$discriminatorKey];
    }

    /**
     * Validates that no unknown keys are present in the input data.
     *
     * @param ReflectionClass<object> $reflection
     * @param \ReflectionMethod|null $constructor
     * @param array<string, mixed> $data
     */
    private function validateUnknownKeys(
        ReflectionClass $reflection,
        ?\ReflectionMethod $constructor,
        array $data
    ): void {
        $knownKeys = [];

        // Collect keys from constructor parameters
        if ($constructor) {
            foreach ($constructor->getParameters() as $parameter) {
                $dateTimeAttributes = $parameter->getAttributes(MapDateTimeProperty::class);
                $propertyAttributes = $parameter->getAttributes(MapProperty::class);
                $path = $this->getPathFromParameter($dateTimeAttributes, $propertyAttributes);
                // If path is used, we can't validate keys in strict mode for nested paths
                if ($path !== null) {
                    // For now, we skip validation for path-based properties
                    continue;
                }
                $knownKeys[] = $this->getJsonKeyFromParameter($parameter, $dateTimeAttributes, $propertyAttributes);
            }
        }

        // Collect keys from properties
        foreach ($reflection->getProperties() as $property) {
            $dateTimeAttributes = $property->getAttributes(MapDateTimeProperty::class);
            $propertyAttributes = $property->getAttributes(MapProperty::class);
            $path = $this->getPathFromProperty($dateTimeAttributes, $propertyAttributes);
            // If path is used, we can't validate keys in strict mode for nested paths
            if ($path !== null) {
                continue;
            }
            $jsonKey = $this->getJsonKeyFromProperty($property, $dateTimeAttributes, $propertyAttributes);
            if (!in_array($jsonKey, $knownKeys, true)) {
                $knownKeys[] = $jsonKey;
            }
        }

        // Check for unknown keys
        foreach (array_keys($data) as $inputKey) {
            if (!in_array($inputKey, $knownKeys, true)) {
                $fullPath = $this->buildFullFieldPath((string) $inputKey);
                $this->errors[$fullPath] = "Unknown key '{$inputKey}' at path '{$fullPath}' is not allowed in strict mode";
            }
        }
    }

    /**
     * Gets path from parameter attributes.
     *
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttributes
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttributes
     * @return string|null
     */
    private function getPathFromParameter(array $dateTimeAttributes, array $propertyAttributes): ?string
    {
        // MapDateTimeProperty has priority
        if (!empty($dateTimeAttributes)) {
            $attr = $dateTimeAttributes[0]->newInstance();
            return $attr->path;
        }

        if (!empty($propertyAttributes)) {
            $attr = $propertyAttributes[0]->newInstance();
            return $attr->path;
        }

        return null;
    }

    /**
     * Gets path from property attributes.
     *
     * @param array<\ReflectionAttribute<MapDateTimeProperty>> $dateTimeAttributes
     * @param array<\ReflectionAttribute<MapProperty>> $propertyAttributes
     * @return string|null
     */
    private function getPathFromProperty(array $dateTimeAttributes, array $propertyAttributes): ?string
    {
        // MapDateTimeProperty has priority
        if (!empty($dateTimeAttributes)) {
            $attr = $dateTimeAttributes[0]->newInstance();
            return $attr->path;
        }

        if (!empty($propertyAttributes)) {
            $attr = $propertyAttributes[0]->newInstance();
            return $attr->path;
        }

        return null;
    }

    /**
     * Builds a detailed error message for path resolution failures.
     *
     * @param string $propertyName Property or parameter name
     * @param string $path The property path that failed
     * @param array<string, mixed> $data The input data
     * @param string $fullPath Full path including any prefix
     * @return string Detailed error message with context
     */
    private function buildPathResolutionError(string $propertyName, string $path, array $data, string $fullPath): string
    {
        // Parse the path to find where it fails
        $segments = explode('.', str_replace(['[', ']'], ['.', ''], $path));
        $current = $data;
        $failedAt = null;
        $availableKeys = [];

        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }

            if (!is_array($current)) {
                $failedAt = implode('.', array_slice($segments, 0, $index));
                break;
            }

            // Check if it's a numeric index
            if (is_numeric($segment)) {
                $idx = (int) $segment;
                if (!isset($current[$idx])) {
                    $failedAt = implode('.', array_slice($segments, 0, $index + 1));
                    $availableKeys = array_keys($current);
                    break;
                }
                $current = $current[$idx];
            } else {
                if (!array_key_exists($segment, $current)) {
                    $failedAt = implode('.', array_slice($segments, 0, $index + 1));
                    $availableKeys = array_keys($current);
                    break;
                }
                $current = $current[$segment];
            }
        }

        // Build error message with context
        $message = "Missing required property '{$propertyName}' at path '{$fullPath}'";

        if ($failedAt !== null) {
            $message .= " (path resolution failed at '{$failedAt}'";

            if (!empty($availableKeys)) {
                // Filter out numeric keys for cleaner output
                $namedKeys = array_filter($availableKeys, fn($k) => !is_int($k));
                if (!empty($namedKeys)) {
                    $keysList = implode(', ', array_slice($namedKeys, 0, 5));
                    if (count($namedKeys) > 5) {
                        $keysList .= ', ...';
                    }
                    $message .= ", available keys: [{$keysList}]";
                } else {
                    // All keys are numeric - it's an array
                    $message .= ", array has " . count($availableKeys) . " elements";
                }
            }

            $message .= ')';
        }

        return $message;
    }
}
