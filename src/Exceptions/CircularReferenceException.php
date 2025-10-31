<?php

declare(strict_types=1);

namespace Pocta\DataMapper\Exceptions;

use RuntimeException;

/**
 * Exception thrown when a circular reference is detected during normalization.
 */
class CircularReferenceException extends RuntimeException
{
}
