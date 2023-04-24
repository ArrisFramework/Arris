<?php

namespace Arris\Exceptions;

use Arris\ExceptionInterface;
use Throwable;

/**
 * Кидается, если обнаружена для переданного URI использован недопустимый метод.
 * Как правило, эта ситуация эквивалентна ошибке 405
 *
 * Дополнительные параметры:
 * - uri
 * - method
 * - routing info
 *
 * Можно получить в обработчике исключения с помощью метода getInfo()
 */
class AppRouterMethodNotAllowedException extends AppRouterException implements ExceptionInterface
{
    protected array $_info;

    /**
     * Exception constructor
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @param array $info
     */
    public function __construct(string $message = "", int $code = 0 , Throwable $previous = null, array $info = [])
    {
        $this->_info = $info;

        parent::__construct($message, $code, $previous, $info);
    }

    /**
     * Get custom field
     *
     * @param $key
     * @return array|mixed|null
     */
    public function getInfo($key = null)
    {
        return is_null($key) ? $this->_info : (array_key_exists($key, $this->_info) ? $this->_info[$key] : null);
    }
}
