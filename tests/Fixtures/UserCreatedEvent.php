<?php

declare(strict_types=1);

namespace Tests\Fixtures;

class UserCreatedEvent extends Event
{
    private int $userId;

    private string $email;

    public function __construct(string $timestamp, int $userId, string $email)
    {
        parent::__construct('user_created', $timestamp);
        $this->userId = $userId;
        $this->email = $email;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
