<?php

declare(strict_types=1);

namespace Tests\Fixtures;

class TaskWithEnums
{
    public int $id;
    public string $name;
    public Status $status;
    public Priority $priority;
    public ?Status $optionalStatus = null;

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

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }

    public function getPriority(): Priority
    {
        return $this->priority;
    }

    public function setPriority(Priority $priority): void
    {
        $this->priority = $priority;
    }

    public function getOptionalStatus(): ?Status
    {
        return $this->optionalStatus;
    }

    public function setOptionalStatus(?Status $optionalStatus): void
    {
        $this->optionalStatus = $optionalStatus;
    }
}
