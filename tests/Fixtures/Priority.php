<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/**
 * Example UnitEnum for testing
 */
enum Priority
{
    case Low;
    case Medium;
    case High;
    case Critical;
}
