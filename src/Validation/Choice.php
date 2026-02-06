<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that a value is one of a given set of choices
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Choice implements AssertInterface
{
    /**
     * @param array<mixed> $choices
     * @param bool $strict Use strict comparison (===)
     * @param string|null $message
     */
    public function __construct(
        public readonly array $choices,
        public readonly bool $strict = true,
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

        if (in_array($value, $this->choices, $this->strict)) {
            return null;
        }

        $choicesStr = implode(', ', array_map(function($c) {
            if (is_scalar($c) || (is_object($c) && method_exists($c, '__toString'))) {
                return "'" . (string)$c . "'";
            }
            return "'" . get_debug_type($c) . "'";
        }, $this->choices));
        return $this->message ?? "Property '{$propertyName}' must be one of: {$choicesStr}";
    }
}
