<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Replaces occurrences of a search string with a replacement string
 * Supports regex patterns when $useRegex is true
 *
 * Example:
 * #[ReplaceFilter('_', '-')]
 * public string $slug; // Replace underscores with hyphens
 *
 * #[ReplaceFilter('/[^a-z0-9-]/', '', useRegex: true)]
 * public string $urlSafe; // Keep only alphanumeric and hyphens
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
class ReplaceFilter implements FilterInterface
{
    public function __construct(
        public readonly string $search,
        public readonly string $replace,
        public readonly bool $useRegex = false,
        public readonly bool $caseInsensitive = false
    ) {
    }

    public function apply(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if ($this->useRegex) {
            return preg_replace($this->search, $this->replace, $value);
        }

        if ($this->caseInsensitive) {
            return str_ireplace($this->search, $this->replace, $value);
        }

        return str_replace($this->search, $this->replace, $value);
    }
}
