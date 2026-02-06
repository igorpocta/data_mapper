<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

/**
 * Interface for custom constraint validators with DI support.
 */
interface ConstraintValidatorInterface
{
    /**
     * Validate a value.
     *
     * @param mixed $value Value to validate
     * @param object $constraint Constraint attribute instance (for accessing parameters)
     * @param object $object The entire object being validated (for cross-field validation)
     * @return string|null Error message if validation fails, null if passes
     */
    public function validate(mixed $value, object $constraint, object $object): ?string;
}
