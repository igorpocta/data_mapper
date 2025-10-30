<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Attributes\PropertyType;

class TestClass
{
    #[MapProperty]
    private int $id;

    #[MapProperty]
    private string $name;

    #[MapProperty]
    private bool $active;

    #[MapProperty(name: 'user_age')]
    private int $age;

    #[MapProperty(name: 'is_admin', type: PropertyType::Bool)]
    private bool $isAdmin;

    private string $unmappedProperty;

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

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): void
    {
        $this->isAdmin = $isAdmin;
    }

    public function getUnmappedProperty(): string
    {
        return $this->unmappedProperty;
    }

    public function setUnmappedProperty(string $unmappedProperty): void
    {
        $this->unmappedProperty = $unmappedProperty;
    }
}
