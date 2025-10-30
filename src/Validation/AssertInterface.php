<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

/**
 * Interface for validation assert attributes
 */
interface AssertInterface
{
    /**
     * Validate a value
     *
     * @param mixed $value Value to validate
     * @param string $propertyName Property name (for error messages)
     * @return string|null Error message if validation fails, null if passes
     */
    public function validate(mixed $value, string $propertyName): ?string;
}
