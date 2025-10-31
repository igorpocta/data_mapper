<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Converts strings to kebab-case format (also known as dash-case or lisp-case).
 *
 * Examples:
 * ```php
 * #[KebabCaseFilter]
 * public string $urlSlug;
 * // "helloWorld" → "hello-world"
 * // "HelloWorld" → "hello-world"
 * // "hello_world" → "hello-world"
 * // "hello world" → "hello-world"
 *
 * #[KebabCaseFilter(screaming: true)]
 * public string $httpHeader;
 * // "contentType" → "CONTENT-TYPE" (SCREAMING-KEBAB-CASE)
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KebabCaseFilter implements FilterInterface
{
    /**
     * @param bool $screaming If true, converts to SCREAMING-KEBAB-CASE (default: false)
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

        // Replace underscores and spaces with hyphens
        $value = preg_replace('/[_\s]+/', '-', $value) ?? $value;

        // Insert hyphen before uppercase letters (for camelCase/PascalCase)
        $value = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $value) ?? $value;

        // Convert to lowercase
        $value = mb_strtolower($value);

        // Remove multiple consecutive hyphens
        $value = preg_replace('/-+/', '-', $value) ?? $value;

        // Trim hyphens from start and end
        $value = trim($value, '-');

        // Convert to uppercase if screaming
        if ($this->screaming) {
            $value = mb_strtoupper($value);
        }

        return $value;
    }
}
