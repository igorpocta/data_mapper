<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Transliterates text from one script to another (e.g., Cyrillic to Latin).
 * Uses PHP's intl extension if available, falls back to simple ASCII conversion.
 *
 * Examples:
 * ```php
 * #[TransliterateFilter]
 * public string $name; // "Привет" → "Privet"
 *
 * #[TransliterateFilter(rules: 'Cyrillic-Latin')]
 * public string $text; // "Москва" → "Moskva"
 *
 * #[TransliterateFilter(rules: 'Greek-Latin')]
 * public string $greek; // "Αθήνα" → "Athína"
 *
 * #[TransliterateFilter(removeUnknown: true)]
 * public string $clean; // Removes characters that cannot be transliterated
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class TransliterateFilter implements FilterInterface
{
    /**
     * @param string|null $rules Transliteration rules (e.g., 'Cyrillic-Latin', 'Greek-Latin', 'Any-Latin')
     * @param bool $removeUnknown Remove characters that cannot be transliterated (default: false)
     */
    public function __construct(
        private ?string $rules = null,
        private bool $removeUnknown = false
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

        // Use intl extension if available
        if (extension_loaded('intl')) {
            return $this->transliterateWithIntl($value);
        }

        // Fallback to basic ASCII conversion
        return $this->transliterateBasic($value);
    }

    /**
     * Transliterate using intl extension.
     */
    private function transliterateWithIntl(string $value): string
    {
        $rules = $this->rules ?? 'Any-Latin';

        if ($this->removeUnknown) {
            $rules .= '; Latin-ASCII; [:Nonspacing Mark:] Remove; [:Punctuation:] Remove; NFC';
        } else {
            $rules .= '; Latin-ASCII';
        }

        $transliterator = \Transliterator::create($rules);

        if ($transliterator === null) {
            return $this->transliterateBasic($value);
        }

        $result = $transliterator->transliterate($value);

        return $result !== false ? $result : $value;
    }

    /**
     * Basic ASCII transliteration fallback.
     */
    private function transliterateBasic(string $value): string
    {
        // Common character replacements
        $replacements = [
            // Cyrillic
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
            'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'Kh', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Shch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
            'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'shch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
            // Greek
            'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E',
            'Ζ' => 'Z', 'Η' => 'I', 'Θ' => 'Th', 'Ι' => 'I', 'Κ' => 'K',
            'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => 'X', 'Ο' => 'O',
            'Π' => 'P', 'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y',
            'Φ' => 'F', 'Χ' => 'Ch', 'Ψ' => 'Ps', 'Ω' => 'O',
            'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e',
            'ζ' => 'z', 'η' => 'i', 'θ' => 'th', 'ι' => 'i', 'κ' => 'k',
            'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => 'x', 'ο' => 'o',
            'π' => 'p', 'ρ' => 'r', 'σ' => 's', 'ς' => 's', 'τ' => 't',
            'υ' => 'y', 'φ' => 'f', 'χ' => 'ch', 'ψ' => 'ps', 'ω' => 'o',
        ];

        $value = strtr($value, $replacements);

        if ($this->removeUnknown) {
            // Remove non-ASCII characters
            $value = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $value) ?? $value;
        }

        return $value;
    }
}
