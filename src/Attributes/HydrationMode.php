<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Attributes;

/**
 * Mode controlling what payload is sent into the hydration function.
 */
enum HydrationMode: string
{
    case VALUE = 'value';   // The property's current raw value
    case PARENT = 'parent'; // The immediate parent payload (current object array)
    case FULL = 'full';     // The top-most/root payload available
}

