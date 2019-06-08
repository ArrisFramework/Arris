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
    public static function init($app_instance_id, $options);

    public static function addScope($scope, $options);

    public static function scope($scope = null):Logger;


}

/**
 * Class AppLogger
 * @package Arris\Arris
 */
class AppLogger implements AppLoggerInterface
{
    const VERSION = "1.14";

    const APPLOGGER_ERROR_OPTIONS_EMPTY = 1;
    const APPLOGGER_ERROR_LOGFILENAME_EMPTY = 2;

    const SCOPE_DELIMETER = '.';

    /**
     * @var array
     */
    private static $_global_config = [];

    /**
     * @var $app_instance
     */
    private static $app_instance;

    /**
     * @var array $_instances \Monolog
     */
    private static $_instances = [];

    /**
     * @param $app_instance_id
     * @param $options array
     *      * bubbling => значение "messages that are handled can bubble up the stack or not", по умолчанию FALSE
     *      * default_log_level - default log level
     *
     */
    public static function init($app_instance_id, $options = [])
    {
        self::$app_instance
            = $app_instance_id;

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
    }

    /**
     * Добавляет скоуп
     *
     * @param $scope
     * @param $options
     */
    public static function addScope($scope, $options)
    {
        $key = self::getScopeKey($scope);

        $logger_name = self::$_global_config['add_scope_to_log'] ? $key : self::$app_instance;

        $logger = new Logger($logger_name);

        if (empty($options)) {
            $options = [
                [ '100-debug.log', Logger::DEBUG ],
                [ '200-info.log', Logger::INFO],
                [ '250-notice.log', Logger::NOTICE],
                [ '300-warning.log', Logger::WARNING],
                [ '400-error.log', Logger::ERROR],
                [ '500-critical.log', Logger::CRITICAL],
                [ '550-alert.log', Logger::ALERT],
                [ '600-emergency.log', Logger::EMERGENCY]
            ];
        }

        foreach ($options as $option) {
            $filename = self::$_global_config['default_logfile_path'] . self::$_global_config['default_logfile_prefix'] . $option[0];
            $loglevel = $option[1] ?? self::$_global_config['default_log_level'];
            $buggling = $option[2] ?? self::$_global_config['bubbling'];

            if (is_null($filename))
                throw new \Exception("AppLogger Class reports: given empty log filename for scope `{$scope}`", self::APPLOGGER_ERROR_LOGFILENAME_EMPTY);

            $logger->pushHandler(new StreamHandler($filename, $loglevel, $buggling ));
        }

        self::$_instances[ $key ] = $logger;
        unset($logger);
    }

    /**
     * Получает скоуп
     *
     * @param null $scope
     * @return Logger
     */
    public static function scope($scope = null):Logger
    {
        $key = self::getScopeKey( $scope );

        if (!self::checkInstance($key))
        {
            die("AppLogger Class reports: given scope {$key} does not exists");
        }

        return self::$_instances[ $key ];
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
        return self::$app_instance . ($scope ? (self::SCOPE_DELIMETER . (string)$scope) : '');
    }

}

# -eof-
