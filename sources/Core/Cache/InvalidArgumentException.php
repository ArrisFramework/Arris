<?php

declare(strict_types=1);

namespace Arris\Core\Cache;

use InvalidArgumentException as RootInvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

class InvalidArgumentException extends RootInvalidArgumentException implements PsrInvalidArgumentException
{
}
