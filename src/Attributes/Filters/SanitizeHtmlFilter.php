<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Sanitizes HTML by stripping tags or allowing only specific tags
 * Useful for user input that may contain HTML but needs to be cleaned
 *
 * Example:
 * #[SanitizeHtmlFilter]
 * public string $description; // Strips all HTML
 *
 * #[SanitizeHtmlFilter('<b><i><u>')]
 * public string $content; // Allows only bold, italic, underline
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class SanitizeHtmlFilter implements FilterInterface
{
    public function __construct(
        public readonly ?string $allowedTags = null
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return strip_tags($value, $this->allowedTags);
    }
}
