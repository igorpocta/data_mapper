<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

/**
 * Interface for custom constraint attributes that delegate validation to an external class.
 */
interface ConstraintInterface extends AssertInterface
{
    /**
     * Returns the FQCN of the validator class that handles this constraint.
     *
     * @return class-string<ConstraintValidatorInterface>
     */
    public function validatedBy(): string;
}
