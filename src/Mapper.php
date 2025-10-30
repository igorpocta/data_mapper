<?php

declare(strict_types=1);

namespace Pocta\DataMapper;

use Pocta\DataMapper\Cache\ArrayCache;
use Pocta\DataMapper\Cache\CacheInterface;
use Pocta\DataMapper\Cache\ClassMetadataFactory;
use Pocta\DataMapper\Denormalizer\Denormalizer;
use Pocta\DataMapper\Normalizer\Normalizer;
use Pocta\DataMapper\Types\TypeResolver;
use Pocta\DataMapper\Events\EventDispatcher;
use Pocta\DataMapper\Events\PreDenormalizeEvent;
use Pocta\DataMapper\Events\PostDenormalizeEvent;
use Pocta\DataMapper\Events\PreNormalizeEvent;
use Pocta\DataMapper\Events\PostNormalizeEvent;
use Pocta\DataMapper\Events\DenormalizationErrorEvent;
use Pocta\DataMapper\Events\ValidationEvent;
use Pocta\DataMapper\Validation\Validator;
use Pocta\DataMapper\Debug\Debugger;
use Pocta\DataMapper\Debug\Profiler;
use InvalidArgumentException;
use JsonException;

class Mapper
{
    private Denormalizer $denormalizer;
    private Normalizer $normalizer;
    private ClassMetadataFactory $metadataFactory;
    private EventDispatcher $eventDispatcher;
    private Validator $validator;
    private TypeResolver $typeResolver;
    private bool $autoValidate;
    private bool $strictMode;
    private ?Debugger $debugger;
    private ?Profiler $profiler;

    /**
     * @param MapperOptions|null $options Mapper configuration options
     * @param Denormalizer|null $denormalizer Custom denormalizer instance
     * @param Normalizer|null $normalizer Custom normalizer instance
     * @param CacheInterface|null $cache Cache implementation (null = default ArrayCache, use NullCache to disable)
     * @param TypeResolver|null $typeResolver Custom type resolver instance
     * @param EventDispatcher|null $eventDispatcher Event dispatcher for hooks (null = creates new one)
     * @param Validator|null $validator Custom validator instance
     * @param Debugger|null $debugger Debugger for logging operations (null = disabled)
     * @param Profiler|null $profiler Profiler for measuring performance (null = disabled)
     */
    public function __construct(
        ?MapperOptions $options = null,
        ?Denormalizer $denormalizer = null,
        ?Normalizer $normalizer = null,
        ?CacheInterface $cache = null,
        ?TypeResolver $typeResolver = null,
        ?EventDispatcher $eventDispatcher = null,
        ?Validator $validator = null,
        ?Debugger $debugger = null,
        ?Profiler $profiler = null
    ) {
        $options = $options ?? new MapperOptions();

        $cache = $cache ?? new ArrayCache();
        $this->typeResolver = $typeResolver ?? new TypeResolver();
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();
        $this->metadataFactory = new ClassMetadataFactory($cache);
        $this->validator = $validator ?? new Validator();
        $this->autoValidate = $options->autoValidate;
        $this->strictMode = $options->strictMode;
        $this->debugger = $debugger;
        $this->profiler = $profiler;

        $this->denormalizer = $denormalizer ?? new Denormalizer($this->typeResolver, $this->metadataFactory);
        $this->denormalizer->setStrictMode($this->strictMode);
        $this->normalizer = $normalizer ?? new Normalizer($this->typeResolver, $this->metadataFactory);

        // Setup event listener for debugging events
        if ($this->debugger?->isEnabled()) {
            $this->setupDebugEventListeners();
        }
    }

    /**
     * Get the metadata factory (for cache management)
     */
    public function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->metadataFactory;
    }

    /**
     * Get the event dispatcher
     */
    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    /**
     * Clear metadata cache for specific class or all classes
     *
     * @param class-string|null $className
     */
    public function clearCache(?string $className = null): void
    {
        $this->metadataFactory->clearCache($className);
    }

    /**
     * Add event listener
     *
     * @param string $eventName Event class name
     * @param callable $listener Callable accepting EventInterface
     * @param int $priority Higher priority = called first (default: 0)
     */
    public function addEventListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->eventDispatcher->addEventListener($eventName, $listener, $priority);
    }

    /**
     * Remove event listener
     */
    public function removeEventListener(string $eventName, callable $listener): void
    {
        $this->eventDispatcher->removeEventListener($eventName, $listener);
    }

    /**
     * Get the validator
     */
    public function getValidator(): Validator
    {
        return $this->validator;
    }

    /**
     * Get the debugger
     */
    public function getDebugger(): ?Debugger
    {
        return $this->debugger;
    }

    /**
     * Get the profiler
     */
    public function getProfiler(): ?Profiler
    {
        return $this->profiler;
    }

    /**
     * Enable or disable auto-validation
     */
    public function setAutoValidate(bool $autoValidate): void
    {
        $this->autoValidate = $autoValidate;
    }

    /**
     * Check if auto-validation is enabled
     */
    public function isAutoValidate(): bool
    {
        return $this->autoValidate;
    }

    /**
     * Enable or disable strict mode
     */
    public function setStrictMode(bool $strictMode): void
    {
        $this->strictMode = $strictMode;
        $this->denormalizer->setStrictMode($strictMode);
    }

    /**
     * Check if strict mode is enabled
     */
    public function isStrictMode(): bool
    {
        return $this->strictMode;
    }

    /**
     * Manually validate an object
     *
     * @param object $object
     * @param bool $throw Whether to throw exception on validation failure
     * @return array<string, string> Array of errors (empty if valid)
     * @throws \Pocta\DataMapper\Exceptions\ValidationException
     */
    public function validate(object $object, bool $throw = true): array
    {
        return $this->validator->validate($object, $throw);
    }

    /**
     * Maps JSON string to an object of the specified class
     *
     * @template T of object
     * @param string $json
     * @param class-string<T> $className
     * @return T
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    public function fromJson(string $json, string $className): object
    {
        $this->profiler?->start('fromJson');
        $this->debugger?->logOperation('fromJson', $json, $className);

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                throw new InvalidArgumentException('JSON must decode to an associative array');
            }

            /** @var array<string, mixed> $data */
            return $this->fromArray($data, $className);
        } finally {
            $this->profiler?->stop('fromJson');
        }
    }

    /**
     * Maps an associative array to an object of the specified class
     *
     * @template T of object
     * @param array<string, mixed> $data
     * @param class-string<T> $className
     * @return T
     */
    public function fromArray(array $data, string $className): object
    {
        $this->profiler?->start('fromArray');
        $this->debugger?->logOperation('fromArray', $data, $className);

        // Dispatch PreDenormalize event
        $preEvent = new PreDenormalizeEvent($data, $className);
        $this->eventDispatcher->dispatch($preEvent);
        $data = $preEvent->data;

        \Pocta\DataMapper\Denormalizer\Denormalizer::setGlobalRoot($data);
        try {
            $this->profiler?->start('denormalize');

            /** @var T $object */
            $object = $this->denormalizer->denormalize($data, $className);

            $this->profiler?->stop('denormalize');

            // Dispatch PostDenormalize event
            $postEvent = new PostDenormalizeEvent($object, $data, $className);
            $this->eventDispatcher->dispatch($postEvent);
            /** @var T $object */
            $object = $postEvent->object;

            // Auto-validation if enabled
            if ($this->autoValidate) {
                $this->profiler?->start('validation');

                $errors = $this->validator->validate($object, throw: false);

                // Dispatch validation event
                $validationEvent = new ValidationEvent($object, $errors);
                $this->eventDispatcher->dispatch($validationEvent);

                $this->profiler?->stop('validation');

                // Throw if there are errors
                if ($validationEvent->hasErrors()) {
                    throw new \Pocta\DataMapper\Exceptions\ValidationException($validationEvent->errors);
                }
            }

            return $object;
        } catch (\Throwable $e) {
            // Dispatch error event
            $errorEvent = new DenormalizationErrorEvent($e, $data, $className);
            $this->eventDispatcher->dispatch($errorEvent);

            // Re-throw if not suppressed
            if (!$errorEvent->isExceptionSuppressed()) {
                throw $e;
            }

            // Return null if suppressed (would need nullable return type in real impl)
            throw $e; // For now still throw
        } finally {
            \Pocta\DataMapper\Denormalizer\Denormalizer::setGlobalRoot(null);
            $this->profiler?->stop('fromArray');
        }
    }

    /**
     * Merges partial data from array into an existing object
     * Only updates properties that are present in the input data
     *
     * @template T of object
     * @param array<string, mixed> $data Partial data to merge
     * @param T $target Existing object to update
     * @param bool $skipNull Skip null values in input data (don't overwrite with null)
     * @return T Updated object (same instance)
     * @throws \Pocta\DataMapper\Exceptions\ValidationException
     */
    public function merge(array $data, object $target, bool $skipNull = false): object
    {
        $this->profiler?->start('merge');
        $this->debugger?->logOperation('merge', $data, get_class($target));

        try {
            $reflection = new \ReflectionClass($target);

            foreach ($data as $key => $value) {
                // Skip null values if requested
                if ($skipNull && $value === null) {
                    continue;
                }

                // Try to find property by checking both direct name and MapProperty attribute
                $property = $this->findPropertyByJsonKey($reflection, $key);

                if ($property === null) {
                    // Property not found
                    if ($this->strictMode) {
                        throw new \Pocta\DataMapper\Exceptions\ValidationException([
                            $key => "Unknown key '{$key}' is not allowed in strict mode"
                        ]);
                    }
                    continue; // Skip unknown properties in non-strict mode
                }

                // Get property type information
                $dateTimeAttributes = $property->getAttributes(\Pocta\DataMapper\Attributes\MapDateTimeProperty::class);
                $propertyAttributes = $property->getAttributes(\Pocta\DataMapper\Attributes\MapProperty::class);

                $typeName = $this->getPropertyType($property, $dateTimeAttributes, $propertyAttributes);
                $format = $this->getFormatFromAttributes($dateTimeAttributes);
                $timezone = $this->getTimezoneFromAttributes($dateTimeAttributes);
                $arrayOf = $this->getArrayOfFromAttributes($dateTimeAttributes, $propertyAttributes);
                $classType = $this->getClassTypeFromAttributes($propertyAttributes);

                // Denormalize the value
                $type = $this->typeResolver->getType($typeName, $format, $timezone, $arrayOf, $classType);
                $isNullable = $property->getType()?->allowsNull() ?? true;
                $typedValue = $type->denormalize($value, $key, $isNullable);

                // Set the value
                $property->setAccessible(true);
                $property->setValue($target, $typedValue);
            }

            return $target;
        } finally {
            $this->profiler?->stop('merge');
        }
    }

    /**
     * Finds a property by its JSON key (considering MapProperty attributes)
     *
     * @param \ReflectionClass<object> $reflection
     * @param string $jsonKey
     * @return \ReflectionProperty|null
     */
    private function findPropertyByJsonKey(\ReflectionClass $reflection, string $jsonKey): ?\ReflectionProperty
    {
        foreach ($reflection->getProperties() as $property) {
            // Check direct match
            if ($property->getName() === $jsonKey) {
                return $property;
            }

            // Check MapProperty name
            $propertyAttributes = $property->getAttributes(\Pocta\DataMapper\Attributes\MapProperty::class);
            if (!empty($propertyAttributes)) {
                $attr = $propertyAttributes[0]->newInstance();
                if ($attr->name === $jsonKey) {
                    return $property;
                }
            }

            // Check MapDateTimeProperty name
            $dateTimeAttributes = $property->getAttributes(\Pocta\DataMapper\Attributes\MapDateTimeProperty::class);
            if (!empty($dateTimeAttributes)) {
                $attr = $dateTimeAttributes[0]->newInstance();
                if ($attr->name === $jsonKey) {
                    return $property;
                }
            }
        }

        return null;
    }

    /**
     * Gets property type considering attributes
     *
     * @param \ReflectionProperty $property
     * @param array<\ReflectionAttribute<\Pocta\DataMapper\Attributes\MapDateTimeProperty>> $dateTimeAttributes
     * @param array<\ReflectionAttribute<\Pocta\DataMapper\Attributes\MapProperty>> $propertyAttributes
     * @return string
     */
    private function getPropertyType(
        \ReflectionProperty $property,
        array $dateTimeAttributes,
        array $propertyAttributes
    ): string {
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
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        return 'string';
    }

    /**
     * @param array<\ReflectionAttribute<\Pocta\DataMapper\Attributes\MapDateTimeProperty>> $dateTimeAttributes
     */
    private function getFormatFromAttributes(array $dateTimeAttributes): ?string
    {
        if (empty($dateTimeAttributes)) {
            return null;
        }
        return $dateTimeAttributes[0]->newInstance()->format;
    }

    /**
     * @param array<\ReflectionAttribute<\Pocta\DataMapper\Attributes\MapDateTimeProperty>> $dateTimeAttributes
     */
    private function getTimezoneFromAttributes(array $dateTimeAttributes): ?string
    {
        if (empty($dateTimeAttributes)) {
            return null;
        }
        return $dateTimeAttributes[0]->newInstance()->timezone;
    }

    /**
     * @param array<\ReflectionAttribute<\Pocta\DataMapper\Attributes\MapDateTimeProperty>> $dateTimeAttributes
     * @param array<\ReflectionAttribute<\Pocta\DataMapper\Attributes\MapProperty>> $propertyAttributes
     * @return class-string|null
     */
    private function getArrayOfFromAttributes(array $dateTimeAttributes, array $propertyAttributes): ?string
    {
        if (!empty($dateTimeAttributes)) {
            return $dateTimeAttributes[0]->newInstance()->arrayOf;
        }
        if (!empty($propertyAttributes)) {
            return $propertyAttributes[0]->newInstance()->arrayOf;
        }
        return null;
    }

    /**
     * @param array<\ReflectionAttribute<\Pocta\DataMapper\Attributes\MapProperty>> $propertyAttributes
     * @return class-string|null
     */
    private function getClassTypeFromAttributes(array $propertyAttributes): ?string
    {
        if (empty($propertyAttributes)) {
            return null;
        }
        return $propertyAttributes[0]->newInstance()->classType;
    }

    /**
     * Maps a collection of arrays to a collection of objects
     *
     * @template T of object
     * @param array<int, array<string, mixed>> $collection Array of associative arrays
     * @param class-string<T> $className
     * @return array<int, T> Array of objects
     * @throws \Pocta\DataMapper\Exceptions\ValidationException
     */
    public function fromArrayCollection(array $collection, string $className): array
    {
        $this->profiler?->start('fromArrayCollection');
        $this->debugger?->logOperation('fromArrayCollection', ['count' => count($collection)], $className);

        try {
            $results = [];
            foreach ($collection as $index => $data) {
                $results[] = $this->fromArray($data, $className);
            }
            return $results;
        } finally {
            $this->profiler?->stop('fromArrayCollection');
        }
    }

    /**
     * Maps a JSON array to a collection of objects
     *
     * @template T of object
     * @param string $json JSON array string
     * @param class-string<T> $className
     * @return array<int, T> Array of objects
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws \Pocta\DataMapper\Exceptions\ValidationException
     */
    public function fromJsonCollection(string $json, string $className): array
    {
        $this->profiler?->start('fromJsonCollection');
        $this->debugger?->logOperation('fromJsonCollection', $json, $className);

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                throw new InvalidArgumentException('JSON must decode to an array');
            }

            /** @var array<int, array<string, mixed>> $data */
            return $this->fromArrayCollection($data, $className);
        } finally {
            $this->profiler?->stop('fromJsonCollection');
        }
    }

    /**
     * Maps an object to JSON string
     *
     * @param object $object
     * @return string
     * @throws JsonException
     */
    public function toJson(object $object): string
    {
        $this->profiler?->start('toJson');
        $this->debugger?->logOperation('toJson', $object);

        try {
            $data = $this->normalizer->normalize($object);
            return json_encode($data, JSON_THROW_ON_ERROR);
        } finally {
            $this->profiler?->stop('toJson');
        }
    }

    /**
     * Maps a collection of objects to JSON array string
     *
     * @param array<int, object> $collection Array of objects
     * @return string JSON array string
     * @throws JsonException
     */
    public function toJsonCollection(array $collection): string
    {
        $this->profiler?->start('toJsonCollection');
        $this->debugger?->logOperation('toJsonCollection', ['count' => count($collection)]);

        try {
            $data = $this->toArrayCollection($collection);
            return json_encode($data, JSON_THROW_ON_ERROR);
        } finally {
            $this->profiler?->stop('toJsonCollection');
        }
    }

    /**
     * Maps an object to an associative array
     *
     * @param object $object
     * @return array<string, mixed>
     */
    public function toArray(object $object): array
    {
        $this->profiler?->start('toArray');
        $this->debugger?->logOperation('toArray', $object);

        try {
            // Dispatch PreNormalize event
            $preEvent = new PreNormalizeEvent($object);
            $this->eventDispatcher->dispatch($preEvent);
            $object = $preEvent->object;

            $this->profiler?->start('normalize');

            $data = $this->normalizer->normalize($object);

            $this->profiler?->stop('normalize');

            // Dispatch PostNormalize event
            $postEvent = new PostNormalizeEvent($data, $object);
            $this->eventDispatcher->dispatch($postEvent);

            return $postEvent->data;
        } finally {
            $this->profiler?->stop('toArray');
        }
    }

    /**
     * Maps a collection of objects to an array of associative arrays
     *
     * @param array<int, object> $collection Array of objects
     * @return array<int, array<string, mixed>> Array of associative arrays
     */
    public function toArrayCollection(array $collection): array
    {
        $this->profiler?->start('toArrayCollection');
        $this->debugger?->logOperation('toArrayCollection', ['count' => count($collection)]);

        try {
            $results = [];
            foreach ($collection as $object) {
                $results[] = $this->toArray($object);
            }
            return $results;
        } finally {
            $this->profiler?->stop('toArrayCollection');
        }
    }

    /**
     * Setup debug event listeners
     */
    private function setupDebugEventListeners(): void
    {
        if (!$this->debugger) {
            return;
        }

        // Log all events
        $debugger = $this->debugger;

        $eventTypes = [
            PreDenormalizeEvent::class,
            PostDenormalizeEvent::class,
            PreNormalizeEvent::class,
            PostNormalizeEvent::class,
            DenormalizationErrorEvent::class,
            ValidationEvent::class,
        ];

        foreach ($eventTypes as $eventType) {
            $this->addEventListener($eventType, function (Events\EventInterface $event) use ($debugger) {
                $debugger->logEvent($event);
            }, -999); // Low priority to run after other listeners
        }
    }
}
