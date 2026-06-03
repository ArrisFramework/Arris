<?php

namespace Arris\Exceptions;

use Arris\ExceptionInterface;

class InvalidUrlException extends \InvalidArgumentException implements ExceptionInterface
{
}