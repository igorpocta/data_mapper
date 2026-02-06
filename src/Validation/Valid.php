<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Marks a property for recursive validation of nested objects or arrays of objects.
 *
 * When the Validator encounters this attribute, it will recursively validate
 * the nested object(s) and report errors with dot-notation paths.
 *
 * Example:
 * #[Valid]
 * public ChildDTO $child;  // Errors: child.name, child.email
 *
 * #[Valid]
 * public array $children;  // Errors: children[0].name, children[1].email
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Valid implements AssertInterface
{
    /**
     * @param string|null $message Custom error message (unused - errors come from nested validators)
     * @param array<string> $groups Validation groups
     */
    public function __construct(
        public readonly ?string $message = null,
        public readonly array $groups = ['Default'],
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        // This is a marker attribute. Actual recursive validation
        // is performed by the Validator when it detects this attribute.
        return null;
    }
}
