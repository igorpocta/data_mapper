<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Converts strings to camelCase format.
 *
 * Examples:
 * ```php
 * #[CamelCaseFilter]
 * public string $propertyName;
 * // "hello_world" → "helloWorld"
 * // "hello-world" → "helloWorld"
 * // "hello world" → "helloWorld"
 * // "HELLO_WORLD" → "helloWorld"
 *
 * #[CamelCaseFilter(upperFirst: true)]
 * public string $className;
 * // "hello_world" → "HelloWorld" (PascalCase)
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class CamelCaseFilter implements FilterInterface
{
    /**
     * @param bool $upperFirst If true, capitalizes first character (PascalCase) (default: false)
     */
    public function __construct(
        private bool $upperFirst = false
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

        // Check if already in camelCase format (no separators)
        if (!preg_match('/[_\-\s]/', $value)) {
            // Already processed or single word - return as is if looks like camelCase
            // or process if it's all lowercase/uppercase
            if (preg_match('/^[a-z]+$/', $value) || preg_match('/^[A-Z]+$/', $value)) {
                // Process single lowercase/uppercase word
                $value = ucfirst(strtolower($value));
                if (!$this->upperFirst) {
                    $value = lcfirst($value);
                }
            }
            return $value;
        }

        // Replace separators (underscore, hyphen, space) with delimiter
        $value = preg_replace('/[_\-\s]+/', ' ', $value) ?? $value;

        // Split into words and capitalize each word
        $words = explode(' ', $value);
        $words = array_map('ucfirst', array_map('strtolower', $words));

        // Join words
        $value = implode('', $words);

        // Lowercase first character unless upperFirst is true
        if (!$this->upperFirst && strlen($value) > 0) {
            $value = lcfirst($value);
        }

        return $value;
    }
}
