<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use DateTimeImmutable;
use DateTimeInterface;
use Pocta\DataMapper\Attributes\MapDateTimeProperty;

class EventWithDateTime
{
    public int $id;
    public string $name;
    public DateTimeImmutable $createdAt;
    public ?DateTimeImmutable $updatedAt = null;

    #[MapDateTimeProperty(format: 'd/m/Y H:i')]
    public ?DateTimeInterface $scheduledAt = null;

    #[MapDateTimeProperty(timezone: 'Europe/Prague')]
    public ?DateTimeImmutable $timezone_test = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getScheduledAt(): ?DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?DateTimeInterface $scheduledAt): void
    {
        $this->scheduledAt = $scheduledAt;
    }

    public function getTimezoneTest(): ?DateTimeImmutable
    {
        return $this->timezone_test;
    }

    public function setTimezoneTest(?DateTimeImmutable $timezone_test): void
    {
        $this->timezone_test = $timezone_test;
    }
}
