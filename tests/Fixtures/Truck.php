<?php

declare(strict_types=1);

namespace Tests\Fixtures;

class Truck extends Vehicle
{
    private int $capacity;

    private bool $fourWheelDrive;

    public function __construct(
        string $brand,
        int $year,
        int $capacity = 1000,
        bool $fourWheelDrive = false
    ) {
        parent::__construct('truck', $brand, $year);
        $this->capacity = $capacity;
        $this->fourWheelDrive = $fourWheelDrive;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function isFourWheelDrive(): bool
    {
        return $this->fourWheelDrive;
    }

    public function setCapacity(int $capacity): void
    {
        $this->capacity = $capacity;
    }

    public function setFourWheelDrive(bool $fourWheelDrive): void
    {
        $this->fourWheelDrive = $fourWheelDrive;
    }
}
