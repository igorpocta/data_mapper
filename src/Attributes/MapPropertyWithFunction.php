<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes;

use Attribute;

/**
 * Hydrates a property's value using a user-provided function and a selected payload.
 *
 * The function must be a valid callable string (e.g. 'strtolower', 'Foo\\Bar::method').
 * It will be invoked with exactly one argument: the payload selected by mode.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class MapPropertyWithFunction
{
    /**
     * @param callable-string|array{class-string,string} $function Callable to invoke
     * @param HydrationMode $mode Payload selection mode
     */
    public function __construct(
        public readonly string|array $function,
        public readonly HydrationMode $mode = HydrationMode::VALUE
    ) {
    }
}
