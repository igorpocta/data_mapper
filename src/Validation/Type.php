<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is of a specific type
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Type implements AssertInterface
{
    public function __construct(
        public readonly string $type,
        public readonly ?string $message = null,
        /** @var array<string> */
        public readonly array $groups = ['Default'],
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        $actualType = get_debug_type($value);

        // Normalize type names
        $expectedType = match($this->type) {
            'boolean', 'bool' => 'bool',
            'integer', 'int' => 'int',
            'double', 'float' => 'float',
            'string' => 'string',
            'array' => 'array',
            'object' => 'object',
            'resource' => 'resource',
            default => $this->type,
        };

        // Check built-in types
        if (in_array($expectedType, ['bool', 'int', 'float', 'string', 'array', 'object', 'resource'], true)) {
            $isValid = false;
            if ($expectedType === 'bool' && is_bool($value)) $isValid = true;
            elseif ($expectedType === 'int' && is_int($value)) $isValid = true;
            elseif ($expectedType === 'float' && is_float($value)) $isValid = true;
            elseif ($expectedType === 'string' && is_string($value)) $isValid = true;
            elseif ($expectedType === 'array' && is_array($value)) $isValid = true;
            elseif ($expectedType === 'object' && is_object($value)) $isValid = true;
            elseif ($expectedType === 'resource' && is_resource($value)) $isValid = true;

            if ($isValid) {
                return null;
            }
        }

        // Check class/interface types
        if (class_exists($expectedType) || interface_exists($expectedType)) {
            if ($value instanceof $expectedType) {
                return null;
            }
        }

        return $this->message ?? "Property '{$propertyName}' must be of type '{$expectedType}', got '{$actualType}'";
    }
}
