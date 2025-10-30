<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Pocta\DataMapper\Attributes\MapProperty;

class UserWithConstructor
{
    #[MapProperty]
    private string $email;

    public function __construct(
        #[MapProperty]
        private int $id,
        #[MapProperty]
        private string $name,
        #[MapProperty]
        private bool $active = true
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
