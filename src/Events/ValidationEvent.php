<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Events;

/**
 * Event dispatched during validation
 * Allows custom validation logic or error modification
 */
class ValidationEvent extends AbstractEvent
{
    /**
     * @param object $object Object being validated
     * @param array<string, string> $errors Validation errors (property => message)
     */
    public function __construct(
        public readonly object $object,
        public array $errors
    ) {
    }

    /**
     * Add validation error
     */
    public function addError(string $property, string $message): void
    {
        $this->errors[$property] = $message;
    }

    /**
     * Remove validation error
     */
    public function removeError(string $property): void
    {
        unset($this->errors[$property]);
    }

    /**
     * Check if there are any errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Clear all errors
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Get object class name
     */
    public function getClassName(): string
    {
        return get_class($this->object);
    }
}
