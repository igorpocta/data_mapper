<?php

declare(strict_types=1);

namespace Tests\Fixtures;

class OrderPlacedEvent extends Event
{
    private int $orderId;

    private float $total;

    public function __construct(string $timestamp, int $orderId, float $total)
    {
        parent::__construct('order_placed', $timestamp);
        $this->orderId = $orderId;
        $this->total = $total;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setOrderId(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function setTotal(float $total): void
    {
        $this->total = $total;
    }
}
