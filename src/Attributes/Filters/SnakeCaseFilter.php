<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Converts strings to snake_case format.
 *
 * Examples:
 * ```php
 * #[SnakeCaseFilter]
 * public string $fieldName;
 * // "helloWorld" → "hello_world"
 * // "HelloWorld" → "hello_world"
 * // "hello-world" → "hello_world"
 * // "hello world" → "hello_world"
 *
 * #[SnakeCaseFilter(screaming: true)]
 * public string $constant;
 * // "helloWorld" → "HELLO_WORLD" (SCREAMING_SNAKE_CASE)
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class SnakeCaseFilter implements FilterInterface
{
    /**
     * @param bool $screaming If true, converts to SCREAMING_SNAKE_CASE (default: false)
     */
    public function __construct(
        private bool $screaming = false
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return $value;
        }

        // Replace hyphens and spaces with underscores
        $value = preg_replace('/[\-\s]+/', '_', $value) ?? $value;

        // Insert underscore before uppercase letters (for camelCase/PascalCase)
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $value) ?? $value;

        // Convert to lowercase
        $value = mb_strtolower($value);

        // Remove multiple consecutive underscores
        $value = preg_replace('/_+/', '_', $value) ?? $value;

        // Trim underscores from start and end
        $value = trim($value, '_');

        // Convert to uppercase if screaming
        if ($this->screaming) {
            $value = mb_strtoupper($value);
        }

        return $value;
    }
}
