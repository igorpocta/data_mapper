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
    private bool $autoValidate;
    private ?Debugger $debugger;
    private ?Profiler $profiler;

    /**
     * @param Denormalizer|null $denormalizer Custom denormalizer instance
     * @param Normalizer|null $normalizer Custom normalizer instance
     * @param CacheInterface|null $cache Cache implementation (null = default ArrayCache, use NullCache to disable)
     * @param TypeResolver|null $typeResolver Custom type resolver instance
     * @param EventDispatcher|null $eventDispatcher Event dispatcher for hooks (null = creates new one)
     * @param bool $autoValidate Automatically validate objects after denormalization (default: false)
     * @param Validator|null $validator Custom validator instance
     * @param Debugger|null $debugger Debugger for logging operations (null = disabled)
     * @param Profiler|null $profiler Profiler for measuring performance (null = disabled)
     */
    public function __construct(
        ?Denormalizer $denormalizer = null,
        ?Normalizer $normalizer = null,
        ?CacheInterface $cache = null,
        ?TypeResolver $typeResolver = null,
        ?EventDispatcher $eventDispatcher = null,
        bool $autoValidate = false,
        ?Validator $validator = null,
        ?Debugger $debugger = null,
        ?Profiler $profiler = null
    ) {
        $cache = $cache ?? new ArrayCache();
        $typeResolver = $typeResolver ?? new TypeResolver();
        $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();
        $this->metadataFactory = new ClassMetadataFactory($cache);
        $this->validator = $validator ?? new Validator();
        $this->autoValidate = $autoValidate;
        $this->debugger = $debugger;
        $this->profiler = $profiler;

        $this->denormalizer = $denormalizer ?? new Denormalizer($typeResolver, $this->metadataFactory);
        $this->normalizer = $normalizer ?? new Normalizer($typeResolver, $this->metadataFactory);

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
