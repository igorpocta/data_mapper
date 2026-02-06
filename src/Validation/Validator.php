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
     * @param array<string>|null $groups Validation groups to apply (null = auto-detect)
     * @return array<string, string> Array of property path => error message (empty if valid)
     * @throws ValidationException If validation fails and $throw = true
     */
    public function validate(object $object, bool $throw = true, ?array $groups = null): array
    {
        $activeGroups = $this->resolveGroups($object, $groups);
        $errors = $this->validateObject($object, '', $activeGroups);

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
     * Resolve which validation groups to use
     *
     * @param object $object Object being validated
     * @param array<string>|null $groups Explicitly provided groups
     * @return array<string> Active groups
     */
    private function resolveGroups(object $object, ?array $groups): array
    {
        if ($groups !== null) {
            return $groups;
        }

        if ($object instanceof GroupSequenceProviderInterface) {
            return $object->getGroupSequence();
        }

        return ['Default'];
    }

    /**
     * Validate an object and return errors with path prefixes
     *
     * @param object $object Object to validate
     * @param string $pathPrefix Path prefix for nested error keys
     * @param array<string> $activeGroups Active validation groups
     * @return array<string, string> Flat array of path => error message
     */
    private function validateObject(object $object, string $pathPrefix, array $activeGroups): array
    {
        $reflection = new ReflectionClass($object);
        $errors = [];

        foreach ($reflection->getProperties() as $property) {
            $propertyErrors = $this->validateProperty($property, $object, $pathPrefix, $activeGroups);
            $errors = array_merge($errors, $propertyErrors);
        }

        return $errors;
    }

    /**
     * Validate a single property
     *
     * @param array<string> $activeGroups Active validation groups
     * @return array<string, string> Array of path => error message
     */
    private function validateProperty(ReflectionProperty $property, object $object, string $pathPrefix, array $activeGroups): array
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
                // Check groups on the Valid attribute itself
                if (!$this->matchesGroups($instance, $activeGroups)) {
                    continue;
                }

                // Recursive validation of nested object
                if (is_object($value)) {
                    $nestedErrors = $this->validateObject($value, $fullPath, $activeGroups);
                    $errors = array_merge($errors, $nestedErrors);
                }
                // Recursive validation of array of objects
                if (is_array($value)) {
                    foreach ($value as $index => $item) {
                        if (is_object($item)) {
                            $nestedErrors = $this->validateObject($item, $fullPath . '[' . $index . ']', $activeGroups);
                            $errors = array_merge($errors, $nestedErrors);
                        }
                    }
                }
                continue;
            }

            if ($instance instanceof AssertInterface) {
                // Check groups filtering
                if (!$this->matchesGroups($instance, $activeGroups)) {
                    continue;
                }

                $error = $instance->validate($value, $propertyName);
                if ($error !== null) {
                    $errors[$fullPath] = $error;
                    break; // Take first error per property
                }
            }
        }

        return $errors;
    }

    /**
     * Check if a constraint's groups match the active groups
     *
     * @param object $constraint The constraint instance
     * @param array<string> $activeGroups Active validation groups
     * @return bool True if the constraint should be applied
     */
    private function matchesGroups(object $constraint, array $activeGroups): bool
    {
        if (!property_exists($constraint, 'groups')) {
            // Constraints without groups property are always applied
            return true;
        }

        /** @var array<string> $constraintGroups */
        $constraintGroups = $constraint->groups;

        return !empty(array_intersect($constraintGroups, $activeGroups));
    }
}
