<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates a value using a custom callback function
 *
 * The callback should return null for valid values, or an error message string for invalid values.
 *
 * Example:
 * #[Callback(callback: [MyClass::class, 'validateUsername'])]
 * #[Callback(callback: fn($value) => $value !== 'admin' ? null : 'Username cannot be admin')]
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Callback implements AssertInterface
{
    /**
     * @param callable $callback Callback function (value, propertyName) => ?string
     * @param string|null $message Default error message if callback returns true/false instead of string
     */
    public function __construct(
        public readonly mixed $callback,
        public readonly ?string $message = null,
        /** @var array<string> */
        public readonly array $groups = ['Default'],
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        // @phpstan-ignore-next-line function.alreadyNarrowedType
        if (!is_callable($this->callback)) {
            return "Callback for property '{$propertyName}' is not callable";
        }

        $result = call_user_func($this->callback, $value, $propertyName);

        // If callback returns null, validation passed
        if ($result === null) {
            return null;
        }

        // If callback returns false, validation failed
        if ($result === false) {
            return $this->message ?? "Property '{$propertyName}' failed custom validation";
        }

        // If callback returns true, validation passed
        if ($result === true) {
            return null;
        }

        // If callback returns a string, use it as error message
        if (is_string($result)) {
            return $result;
        }

        return $this->message ?? "Property '{$propertyName}' failed custom validation";
    }
}
