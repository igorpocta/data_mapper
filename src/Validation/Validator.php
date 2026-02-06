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
     * @return array<string, string> Array of property path => error message (empty if valid)
     * @throws ValidationException If validation fails and $throw = true
     */
    public function validate(object $object, bool $throw = true): array
    {
        $errors = $this->validateObject($object, '');

        if (!empty($errors) && $throw) {
            throw new ValidationException($errors);
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

    /**
     * Validate an object and return errors with path prefixes
     *
     * @param object $object Object to validate
     * @param string $pathPrefix Path prefix for nested error keys
     * @return array<string, string> Flat array of path => error message
     */
    private function validateObject(object $object, string $pathPrefix): array
    {
        $reflection = new ReflectionClass($object);
        $errors = [];

        foreach ($reflection->getProperties() as $property) {
            $propertyErrors = $this->validateProperty($property, $object, $pathPrefix);
            $errors = array_merge($errors, $propertyErrors);
        }

        return $errors;
    }

    /**
     * Validate a single property
     *
     * @return array<string, string> Array of path => error message
     */
    private function validateProperty(ReflectionProperty $property, object $object, string $pathPrefix): array
    {
        $errors = [];
        $property->setAccessible(true);

        if (!$property->isInitialized($object)) {
            return $errors;
        }

        $value = $property->getValue($object);
        $propertyName = $property->getName();
        $fullPath = $pathPrefix !== '' ? $pathPrefix . '.' . $propertyName : $propertyName;

        // Get all Assert attributes
        foreach ($property->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();

            if ($instance instanceof Valid) {
                // Recursive validation of nested object
                if (is_object($value)) {
                    $nestedErrors = $this->validateObject($value, $fullPath);
                    $errors = array_merge($errors, $nestedErrors);
                }
                // Recursive validation of array of objects
                if (is_array($value)) {
                    foreach ($value as $index => $item) {
                        if (is_object($item)) {
                            $nestedErrors = $this->validateObject($item, $fullPath . '[' . $index . ']');
                            $errors = array_merge($errors, $nestedErrors);
                        }
                    }
                }
                continue;
            }

            if ($instance instanceof AssertInterface) {
                $error = $instance->validate($value, $propertyName);
                if ($error !== null) {
                    $errors[$fullPath] = $error;
                    break; // Take first error per property (consistent with original behavior)
                }
            }
        }

        return $errors;
    }
}
