<?php

namespace Arris;

use Monolog\Logger;

interface AppLoggerInterface
{
    /**
     * Инициализирует класс логгера
     *
     * @param $application - Имя приложения
     * @param $instance - код инстанса приложения (например, bin2hex(random_bytes(8)) )
     * @param array $options <br>
     * - bubbling           - [FALSE] - всплывает ли логгируемое сообщение?<br>
     *
     * - default_log_level  - [DEBUG] - уровень логгирования по умолчанию <br>
     * - default_logfile_path - [''] - путь к файлам логов по умолчанию<br>
     * - default_logfile_prefix - [''] - префикc файла лога по умолчанию <br>
     * - default_log_file - ['_.log'] имя файла лога по умолчанию, применяется если для имени файла передан NULL<br>
     * - default_handler - [NULL] - хэндлер, реализующий \Monolog\Handler\HandlerInterface как логгер по умолчанию для этого скоупа
     *
     * - add_scope_to_log   - [FALSE] - добавлять ли имя скоупа к имени логгера в файле лога?<br>
     * - deferred_scope_creation - [TRUE] - разрешать ли отложенную инициализацию скоупов <br>
     * - deferred_scope_separate_files - [TRUE] - использовать ли разные файлы для deferred-скоупов (на основе имени скоупа)
     *
     */
    public static function init($application, $instance, $options = []);

    /**
     * Добавляет скоуп
     *
     * @param null $scope - имя скоупа
     * @param array $scope_levels - массив кортежей:
     * [ filename , logging_level, <опции> ], где:
     * - filename - имя файла лога
     * - logging_level - уровень логгирования - уровень логгирования (переменные Logger::DEBUG etc)
     * А опции - возможные ключи:
     * - enabled - [TRUE], разрешен ли уровень логгирования
     * - bubbling - [FALSE], всплывает ли сообщение логгирования на следующий уровень
     * - handler - NULL либо инстанс Хэндлера, реализующего интерфейс Monolog\Handler\HandlerInterface
     *
     * Если передается пустой массив - загружаются опции по умолчанию, а скоуп считается DEFERRED и к нему применяются
     * правила создания Deferred-скоупов.
     *
     * @param bool $scope_logging_enabled - разрешен ли скоуп вообще для логгирования?
     * @return mixed
     */
    public static function addScope($scope = null, $scope_levels = [], $scope_logging_enabled = true);

    /**
     * Получает скоуп
     *
     * @param null $scope
     * @return Logger
     */
    public static function scope($scope = null):Logger;

    /**
     * Добавляет null-logger
     * @return mixed Logger
     */
    public static function addNullLogger();
}