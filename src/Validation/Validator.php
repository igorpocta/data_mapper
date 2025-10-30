<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Pocta\DataMapper\Exceptions\ValidationException;
use ReflectionClass;
use ReflectionProperty;

/**
 * Validator for objects with Assert attributes
 */
class Validator
{
    /**
     * Validate an object against its Assert attributes
     *
     * @param object $object Object to validate
     * @param bool $throw Whether to throw exception on validation failure
     * @return array<string, string> Array of property => error message (empty if valid)
     * @throws ValidationException If validation fails and $throw = true
     */
    public function validate(object $object, bool $throw = true): array
    {
        $reflection = new ReflectionClass($object);
        $errors = [];

        foreach ($reflection->getProperties() as $property) {
            $propertyErrors = $this->validateProperty($property, $object);
            if (!empty($propertyErrors)) {
                $errors[$property->getName()] = $propertyErrors[0]; // Take first error
            }
        }

        if (!empty($errors) && $throw) {
            throw new ValidationException($errors);
        }

        return $errors;
    }

    /**
     * Validate a single property
     *
     * @return array<string> Array of error messages
     */
    private function validateProperty(ReflectionProperty $property, object $object): array
    {
        $errors = [];
        $property->setAccessible(true);
        $value = $property->getValue($object);

        // Get all Assert attributes
        foreach ($property->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();

            if ($instance instanceof AssertInterface) {
                $error = $instance->validate($value, $property->getName());
                if ($error !== null) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * Check if object is valid (has no validation errors)
     */
    public function isValid(object $object): bool
    {
        $errors = $this->validate($object, throw: false);
        return empty($errors);
    }

    /**
     * Get validation errors without throwing exception
     *
     * @return array<string, string>
     */
    public function getErrors(object $object): array
    {
        return $this->validate($object, throw: false);
    }
}
