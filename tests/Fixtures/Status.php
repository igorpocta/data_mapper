<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/**
 * Example BackedEnum for testing
 */
enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}
