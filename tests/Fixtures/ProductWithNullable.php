<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Pocta\DataMapper\Attributes\MapProperty;

class ProductWithNullable
{
    #[MapProperty]
    private int $id;

    #[MapProperty]
    private string $name;

    #[MapProperty]
    private ?string $description;

    #[MapProperty]
    private ?int $stock;

    #[MapProperty]
    private ?bool $featured;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): void
    {
        $this->stock = $stock;
    }

    public function isFeatured(): ?bool
    {
        return $this->featured;
    }

    public function setFeatured(?bool $featured): void
    {
        $this->featured = $featured;
    }
}
