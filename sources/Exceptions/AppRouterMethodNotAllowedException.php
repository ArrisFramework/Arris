<?php

namespace Arris\Exceptions;

use Arris\ExceptionInterface;

/**
 * Раньше получал сообщение "Method not allowed, valid methods are: " . implode(',', $routeInfo[1]),
 * теперь получает JSONized строку, содержащую URI, METHODS и вообще всё на свете. Остальное возлагается
 * на обработчик исключения
 */
class AppRouterMethodNotAllowedException extends \RuntimeException implements ExceptionInterface {

}
