<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Pocta\DataMapper\Attributes\MapProperty;

class StrictTag
{
    public function __construct(
        #[MapProperty]
        private int $id,
        #[MapProperty]
        private string $name
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
}
