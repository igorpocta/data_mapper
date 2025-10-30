<?php

declare(strict_types=1);

namespace Tests\Fixtures;

class Car extends Vehicle
{
    private int $doors;

    private bool $convertible;

    public function __construct(
        string $brand,
        int $year,
        int $doors = 4,
        bool $convertible = false
    ) {
        parent::__construct('car', $brand, $year);
        $this->doors = $doors;
        $this->convertible = $convertible;
    }

    public function getDoors(): int
    {
        return $this->doors;
    }

    public function isConvertible(): bool
    {
        return $this->convertible;
    }

    public function setDoors(int $doors): void
    {
        $this->doors = $doors;
    }

    public function setConvertible(bool $convertible): void
    {
        $this->convertible = $convertible;
    }
}
