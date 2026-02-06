<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates a value using a custom callback function
 *
 * The callback should return null for valid values, or an error message string for invalid values.
 *
 * On properties:
 * #[Callback(callback: [MyClass::class, 'validateUsername'])]
 * #[Callback(callback: fn($value) => $value !== 'admin' ? null : 'Username cannot be admin')]
 *
 * On methods (no callback parameter needed):
 * #[Callback]
 * public function validateContacts(): array { return []; }
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_METHOD)]
class Callback implements AssertInterface
{
    /**
     * @param callable|null $callback Callback function (value, propertyName) => ?string. Null when used on methods.
     * @param string|null $message Default error message if callback returns true/false instead of string
     */
    public function __construct(
        public readonly mixed $callback = null,
        public readonly ?string $message = null,
        /** @var array<string> */
        public readonly array $groups = ['Default'],
    ) {
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        // When used on a method, callback is null — validation is handled by Validator
        if ($this->callback === null) {
            return null;
        }

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
