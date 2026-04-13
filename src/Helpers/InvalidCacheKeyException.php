<?php

namespace Airalo\Helpers;

use Psr\SimpleCache\InvalidArgumentException;

/**
 * Thrown when a cache key does not conform to PSR-16 requirements.
 */
class InvalidCacheKeyException extends \InvalidArgumentException implements InvalidArgumentException
{
}

