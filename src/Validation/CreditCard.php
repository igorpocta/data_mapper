<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Validation;

use Attribute;

/**
 * Validates that the value is a valid credit card number using the Luhn algorithm
 * Optionally validates specific card types (Visa, MasterCard, Amex, etc.)
 *
 * Example:
 * #[CreditCard]
 * public string $cardNumber;
 *
 * #[CreditCard(types: ['visa', 'mastercard'])]
 * public string $card;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class CreditCard implements AssertInterface
{
    /** @var array<string>|null */
    private ?array $types;

    /**
     * @param array<string>|null $types Allowed card types: visa, mastercard, amex, discover, diners, jcb
     * @param string $message
     */
    public function __construct(
        ?array $types = null,
        public readonly string $message = 'Value must be a valid credit card number',
        /** @var array<string> */
        public readonly array $groups = ['Default'],
    ) {
        $this->types = $types;
    }

    public function validate(mixed $value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return "Property '{$propertyName}' must be string or numeric for credit card validation";
        }

        // Remove spaces and hyphens
        $number = preg_replace('/[\s-]/', '', (string) $value);

        // Must be numeric and 13-19 digits
        if (!ctype_digit($number) || strlen($number) < 13 || strlen($number) > 19) {
            return str_replace('Value', "Property '{$propertyName}'", $this->message);
        }

        // Validate card type if specified
        if ($this->types !== null && !$this->matchesCardType($number)) {
            return str_replace('Value', "Property '{$propertyName}'", $this->message);
        }

        // Validate using Luhn algorithm
        if (!$this->luhnCheck($number)) {
            return str_replace('Value', "Property '{$propertyName}'", $this->message);
        }

        return null;
    }

    

    /**
     * Luhn algorithm check
     */
    private function luhnCheck(string $number): bool
    {
        $sum = 0;
        $length = strlen($number);
        $parity = $length % 2;

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $number[$i];

            if ($i % 2 !== $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
        }

        return $sum % 10 === 0;
    }

    /**
     * Check if card number matches specified types
     */
    private function matchesCardType(string $number): bool
    {
        if ($this->types === null || empty($this->types)) {
            return true;
        }

        $patterns = [
            'visa' => '/^4[0-9]{15}$/',  // 16 digits starting with 4
            'mastercard' => '/^5[1-5][0-9]{14}$|^2[2-7][0-9]{14}$/',
            'amex' => '/^3[47][0-9]{13}$/',
            'discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
            'diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
            'jcb' => '/^(?:2131|1800|35\d{3})\d{11}$/',
        ];

        foreach ($this->types as $type) {
            $type = strtolower($type);
            if (isset($patterns[$type]) && preg_match($patterns[$type], $number)) {
                return true;
            }
        }

        return false;
    }
}
