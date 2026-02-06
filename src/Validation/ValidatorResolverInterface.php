<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

/**
 * Interface for resolving constraint validator instances (e.g. from a DI container).
 */
interface ValidatorResolverInterface
{
    /**
     * Resolve a constraint validator instance by class name.
     *
     * @param class-string<ConstraintValidatorInterface> $validatorClass
     */
    public function resolve(string $validatorClass): ConstraintValidatorInterface;
}
