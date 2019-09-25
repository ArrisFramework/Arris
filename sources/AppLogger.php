<?php /** @noinspection ALL */

/**
 * User: Karel Wintersky
 *
 * Class AppLogger
 * Namespace: Arris
 *
 * Library: https://github.com/KarelWintersky/Arris
 *
 * Date: 24.04.2019, 18:35
 */

namespace Arris;

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

/**
 * Interface AppLoggerInterface
 * @package Arris\Arris
 */
interface AppLoggerInterface
{
    /**
     * Инициализирует класс логгера
     *
     * @param $application - Имя приложения
     * @param $instance - код инстанса приложения (например, bin2hex(random_bytes(8)) )
     * @param array $options:
     * - bubbling           - значение "messages that are handled can bubble up the stack or not", (FALSE)<br>
     * - default_log_level  - default log level (DEBUG) <br>
     * - add_scope_to_log   - добавлять ли имя скоупа к имени логгера в файле (FALSE, DEPRECATED)<br>
     * - default_logfile_path - путь к файлам логов по умолчанию ('') <br>
     * - default_logfile_prefix - префикc файла лога по умолчанию ('') <br>
     * - default_log_file - имя файла лога по умолчанию, применяется если для имени файла передан NULL (_.log)<br>
     * - deferred_scope_creation - разрешать ли отложенную инициализацию скоупов (TRUE) <br>
     *
     */
    public static function init($application, $instance, $options = []);

    /**
     * Добавляет скоуп
     *
     * @param $scope - имя скоупа
     * @param $options - массив кортежей:
     * [ filename , logging_level, use_logger ], где:
     * - filename - имя файла лога
     * - уровень логгирования - уровень логгирования (переменные Logger::DEBUG etc)
     * - use_logger - [true], булево значение, FALSE означает использовать для этого уровня логгирования NULL Handler
     */
    public static function addScope($scope, $options);

    /**
     * Получает скоуп
     *
     * @param null $scope
     * @return Logger
     */
    public static function scope($scope = null):Logger;

    /**
     * Поздняя инициализация скоупа значениями по умолчанию
     *
     * @param null $scope
     */
    public static function addDeferredScope($scope = null);
}

/**
 * Class AppLogger
 * @package Arris\Arris
 */
class AppLogger implements AppLoggerInterface
{
    const VERSION = "1.22";

    const APPLOGGER_ERROR_OPTIONS_EMPTY = 1;
    const APPLOGGER_ERROR_LOGFILENAME_EMPTY = 2;

    const SCOPE_DELIMETER = '.';

    const DEFAULT_LOG_FILENAME = '_.log';

    const DEFAULT_SCOPE_OPTIONS = [
        [ '100-debug.log',      Logger::DEBUG,      TRUE],
        [ '200-info.log',       Logger::INFO,       TRUE],
        [ '250-notice.log',     Logger::NOTICE,     TRUE],
        [ '300-warning.log',    Logger::WARNING,    TRUE],
        [ '400-error.log',      Logger::ERROR,      TRUE],
        [ '500-critical.log',   Logger::CRITICAL,   TRUE],
        [ '550-alert.log',      Logger::ALERT,      TRUE],
        [ '600-emergency.log',  Logger::EMERGENCY,  TRUE]
    ];

    /**
     * @var array
     */
    private static $_global_config = [];

    /**
     * @var string
     */
    private static $application;

    /**
     * @var string
     */
    private static $instance;

    /**
     * @var string $app_instance
     */
    private static $app_instance;

    /**
     * @var array $_instances \Monolog
     */
    private static $_instances = [];

    public static function init($application, $instance, $options = [])
    {
        self::$application
            = $application;

        self::$instance
            = $instance;

        self::$_global_config['bubbling']
            = isset($options['bubbling'])
            ? $options['bubbling']
            : false;

        self::$_global_config['default_log_level']
            = isset($options['default_log_level'])
            ? $options['default_log_level']
            : Logger::DEBUG;

        self::$_global_config['add_scope_to_log']
            = isset($options['add_scope_to_log'])
            ? $options['add_scope_to_log']
            : false;

        self::$_global_config['default_logfile_path']
            = isset($options['default_logfile_path'])
            ? rtrim($options['default_logfile_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            : '';

        self::$_global_config['default_logfile_prefix']
            = isset($options['default_logfile_prefix'])
            ? $options['default_logfile_prefix']
            : '';

        self::$_global_config['default_log_file']
            = isset($options['default_log_file'])
            ? $options['default_log_file']
            : self::DEFAULT_LOG_FILENAME;

        self::$_global_config['deferred_scope_creation']
            = isset($options['deferred_scope_creation'])
            ? $options['deferred_scope_creation']
            : true;
    }

    public static function addScope($scope = null, $options = [])
    {
        //@todo: делать "мерж" переопределенных значений > дефолтных (без if empty() ). Ключ проверки - значение уровня логгирования

        if (empty($options)) {
            $options = self::DEFAULT_SCOPE_OPTIONS;
        }

        try {
            $logger_name = self::getLoggerName($scope);
            $internal_key = self::getScopeKey($scope);

            $logger = new Logger($logger_name);

            foreach ($options as $an_option) {

                $filename = empty($an_option[0]) ? self::$_global_config['default_log_file'] : $an_option[0];

                $filename
                    = self::$_global_config['default_logfile_path']
                    . self::$_global_config['default_logfile_prefix']
                    . $filename;

                $loglevel = $an_option[1] ?? self::$_global_config['default_log_level'];
                $buggling = $an_option[2] ?? self::$_global_config['bubbling'];

                //@todo: more handlers, FALSE is null handler

                if (array_key_exists(2, $an_option) && $an_option[2] == FALSE) {
                    $logger->pushHandler(new \Monolog\Handler\NullHandler());
                } else {
                    $logger->pushHandler(new StreamHandler($filename, $loglevel, $buggling ));
                }

            }

            self::$_instances[ $internal_key ] = $logger;
            unset($logger);
        } catch (\Exception $e) {
            die(__METHOD__ . ' died at line ' .$e->getLine() . ' With exception ' . $e->getMessage() . ' code = ' . $e->getCode() );
        }
    }

    public static function addDeferredScope($scope = null)
    {
        $options = self::DEFAULT_SCOPE_OPTIONS;

        try {
            $logger_name = self::getLoggerName($scope);
            $internal_key = self::getScopeKey($scope);

            $logger = new Logger($logger_name);

            foreach ($options as $an_option) {
                $filename
                    = self::$_global_config['default_logfile_path']
                    . self::$_global_config['default_logfile_prefix']
                    . ($scope ? (string)$scope : '')
                    . ".{$an_option[0]}";

                $loglevel = $an_option[1];
                $buggling = false;

                $logger->pushHandler(new StreamHandler($filename, $loglevel, $buggling ));
            }

            self::$_instances[ $internal_key ] = $logger;
            unset($logger);
        } catch (\Exception $e) {
            die(__METHOD__ . ' died at line ' .$e->getLine() . ' With exception ' . $e->getMessage() . ' code = ' . $e->getCode() );
        }
    }

    public static function scope($scope = null):Logger
    {
        try {
            $internal_key = self::getScopeKey( $scope );

            if (!self::checkInstance($internal_key) and self::$_global_config['deferred_scope_creation']) {
                self::addDeferredScope($scope);
            }

            return self::$_instances[ $internal_key ];

        } catch (\Exception $e) {
            die(__METHOD__ . ' died at line ' .$e->getLine() . ' With exception ' . $e->getMessage() . ' code = ' . $e->getCode() );
        }
    }

    /**
     * Проверяет существование инстанса логгера
     *
     * @param $key
     * @return bool
     */
    private static function checkInstance($key):bool
    {
        return ( array_key_exists($key, self::$_instances) && self::$_instances[$key] !== NULL );
    }

    /**
     * Получает внутренний ключ логгера
     *
     * @param null $scope
     * @return string
     */
    private static function getScopeKey($scope = null)
    {
        $scope ? (self::SCOPE_DELIMETER . (string)$scope) : '';

        return self::$application . self::$instance . $scope;
    }

    /**
     * @param null $scope
     * @return string
     */
    private static function getLoggerName($scope = null)
    {
        $scope = $scope ? (self::SCOPE_DELIMETER . (string)$scope) : '';

        return
            self::$_global_config['add_scope_to_log']
                ? self::$application . self::SCOPE_DELIMETER . self::$instance . self::SCOPE_DELIMETER . $scope
                : self::$application . self::SCOPE_DELIMETER . self::$instance;
    }


}

# -eof-
