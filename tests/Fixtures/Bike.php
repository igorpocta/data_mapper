<?php

declare(strict_types=1);

namespace Tests\Fixtures;

class Bike extends Vehicle
{
    private bool $electric;

    private int $gears;

    public function __construct(
        string $brand,
        int $year,
        bool $electric = false,
        int $gears = 1
    ) {
        parent::__construct('bike', $brand, $year);
        $this->electric = $electric;
        $this->gears = $gears;
    }

    public function isElectric(): bool
    {
        return $this->electric;
    }

    public function getGears(): int
    {
        return $this->gears;
    }

    public function setElectric(bool $electric): void
    {
        $this->electric = $electric;
    }

    public function setGears(int $gears): void
    {
        $this->gears = $gears;
    }
}
