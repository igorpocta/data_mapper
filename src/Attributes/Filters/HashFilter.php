<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes\Filters;

use Attribute;

/**
 * Hashes string values using specified algorithm.
 * Useful for password hashing, data anonymization, and security.
 *
 * Examples:
 * ```php
 * #[HashFilter(algo: 'bcrypt')]
 * public string $password; // "secret123" → "$2y$10$..."
 *
 * #[HashFilter(algo: 'argon2i')]
 * public string $token; // "token123" → "$argon2i$..."
 *
 * #[HashFilter(algo: 'sha256')]
 * public string $checksum; // "data" → "3a6eb079..."
 *
 * #[HashFilter(algo: 'md5')]
 * public string $hash; // "test" → "098f6bcd4621d373cade4e832627b4f6"
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class HashFilter implements FilterInterface
{
    /**
     * @param string $algo Hash algorithm: 'bcrypt', 'argon2i', 'argon2id', 'md5', 'sha1', 'sha256', 'sha512'
     * @param array<string, mixed> $options Algorithm-specific options
     */
    public function __construct(
        private string $algo = 'bcrypt',
        private array $options = []
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

        // Empty strings are not hashed
        if ($value === '') {
            return $value;
        }

        return match ($this->algo) {
            'bcrypt' => $this->bcrypt($value),
            'argon2i' => $this->argon2i($value),
            'argon2id' => $this->argon2id($value),
            'md5' => md5($value),
            'sha1' => sha1($value),
            'sha256' => hash('sha256', $value),
            'sha512' => hash('sha512', $value),
            default => $this->genericHash($value),
        };
    }

    /**
     * Hash using bcrypt (PASSWORD_BCRYPT).
     */
    private function bcrypt(string $value): string
    {
        $options = array_merge([
            'cost' => 10,
        ], $this->options);

        $hash = password_hash($value, PASSWORD_BCRYPT, $options);

        // @phpstan-ignore-next-line - password_hash can return false on failure in some PHP versions
        if (!is_string($hash)) {
            throw new \RuntimeException('Failed to hash password with bcrypt');
        }

        return $hash;
    }

    /**
     * Hash using Argon2i (PASSWORD_ARGON2I).
     */
    private function argon2i(string $value): string
    {
        if (!defined('PASSWORD_ARGON2I')) {
            throw new \RuntimeException('Argon2i is not supported in this PHP installation');
        }

        $options = array_merge([
            'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
            'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
            'threads' => PASSWORD_ARGON2_DEFAULT_THREADS,
        ], $this->options);

        $hash = password_hash($value, PASSWORD_ARGON2I, $options);

        // @phpstan-ignore-next-line - password_hash can return false on failure in some PHP versions
        if (!is_string($hash)) {
            throw new \RuntimeException('Failed to hash password with Argon2i');
        }

        return $hash;
    }

    /**
     * Hash using Argon2id (PASSWORD_ARGON2ID).
     */
    private function argon2id(string $value): string
    {
        if (!defined('PASSWORD_ARGON2ID')) {
            throw new \RuntimeException('Argon2id is not supported in this PHP installation');
        }

        $options = array_merge([
            'memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
            'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
            'threads' => PASSWORD_ARGON2_DEFAULT_THREADS,
        ], $this->options);

        $hash = password_hash($value, PASSWORD_ARGON2ID, $options);

        // @phpstan-ignore-next-line - password_hash can return false on failure in some PHP versions
        if (!is_string($hash)) {
            throw new \RuntimeException('Failed to hash password with Argon2id');
        }

        return $hash;
    }

    /**
     * Hash using generic hash function.
     */
    private function genericHash(string $value): string
    {
        $hash = hash($this->algo, $value);

        // @phpstan-ignore-next-line - hash can return false on failure with invalid algorithm
        if (!is_string($hash)) {
            throw new \RuntimeException("Failed to hash with algorithm '{$this->algo}'");
        }

        return $hash;
    }
}
