<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Pocta\DataMapper\Attributes\MapProperty;
use Pocta\DataMapper\Attributes\PropertyType;

class StrictPost
{
    public function __construct(
        #[MapProperty]
        private int $id,
        #[MapProperty]
        private string $title,
        #[MapProperty]
        private string $content,
        /** @var StrictTag[] */
        #[MapProperty(type: PropertyType::Array, arrayOf: StrictTag::class)]
        private array $tags
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return StrictTag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
