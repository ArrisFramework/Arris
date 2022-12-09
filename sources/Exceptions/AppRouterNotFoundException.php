<?php

namespace Arris\Exceptions;

use Arris\ExceptionInterface;

/**
 * Раньше получал сообщение "URL " . self::$uri . " not found", но теперь должен получать только URI.
 * Обработка лежит на получателе исключения
 */
class AppRouterNotFoundException extends \RuntimeException implements ExceptionInterface  {

}