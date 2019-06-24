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
    const VERSION = "1.21";

    const APPLOGGER_ERROR_OPTIONS_EMPTY = 1;
    const APPLOGGER_ERROR_LOGFILENAME_EMPTY = 2;

    const SCOPE_DELIMETER = '.';

    const DEFAULT_LOG_FILENAME = '_.log';

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
     * Инициализирует класс логгера
     *
     * @param $app_instance_id
     * @param $options array
     * - bubbling => значение "messages that are handled can bubble up the stack or not", (FALSE)<br>
     * - default_log_level - default log level (DEBUG) <br>
     * - add_scope_to_log - добавлять ли имя скоупа к имени файла лога (FALSE) <br>
     * - default_logfile_path - путь к файлам логов по умолчанию ('') <br>
     * - default_logfile_prefix - префикc файла лога по умолчанию ('') <br>
     * - default_log_file - имя файла лога по умолчанию, применяется если для имени файла передан NULL (_.log)<br>
     * - deferred_scope_creation - разрешать ли отложенную инициализацию скоупов (TRUE) <br>
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

        self::$_global_config['default_log_file']
            = isset($options['default_log_file'])
            ? $options['default_log_file']
            : self::DEFAULT_LOG_FILENAME;

        self::$_global_config['deferred_scope_creation']
            = isset($options['deferred_scope_creation'])
            ? $options['deferred_scope_creation']
            : true;

    }

    /**
     * Добавляет скоуп
     *
     * @param $scope
     * @param $options
     */
    public static function addScope($scope = null, $options = [])
    {
        try {
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
                $filename = $options[0] ?? self::$_global_config['default_log_file'];
                $filename = self::$_global_config['default_logfile_path'] . self::$_global_config['default_logfile_prefix'] . $filename;

                $loglevel = $option[1] ?? self::$_global_config['default_log_level'];
                $buggling = $option[2] ?? self::$_global_config['bubbling'];

                /*
                if (is_null($filename))
                    throw new \Exception("AppLogger Class reports: given empty log filename for scope `{$scope}`", self::APPLOGGER_ERROR_LOGFILENAME_EMPTY);
                */

                $logger->pushHandler(new StreamHandler($filename, $loglevel, $buggling ));
            }

            self::$_instances[ $key ] = $logger;
            unset($logger);
        } catch (\Exception $e) {
            die(__METHOD__ . ' died at line ' .$e->getLine() . ' With exception ' . $e->getMessage() . ' code = ' . $e->getCode() );
        }
    }

    /**
     * Поздняя инициализация скоупа значениями по умолчанию
     *
     * @param null $scope
     */
    public static function addDeferredScope($scope = null)
    {
        try {
            $key = self::getScopeKey($scope);

            $logger_name = self::$_global_config['add_scope_to_log'] ? $key : self::$app_instance;

            $logger = new Logger($logger_name);

            $options = [
                [ $key . '-100-debug.log', Logger::DEBUG ],
                [ $key . '-200-info.log', Logger::INFO],
                [ $key . '-250-notice.log', Logger::NOTICE],
                [ $key . '-300-warning.log', Logger::WARNING],
                [ $key . '-400-error.log', Logger::ERROR],
                [ $key . '-500-critical.log', Logger::CRITICAL],
                [ $key . '-550-alert.log', Logger::ALERT],
                [ $key . '-600-emergency.log', Logger::EMERGENCY]
            ];

            foreach ($options as $option) {
                $filename
                    = self::$_global_config['default_logfile_path']
                    . self::$_global_config['default_logfile_prefix']
                    . $filename;

                $loglevel = $option[1];
                $buggling = false;

                $logger->pushHandler(new StreamHandler($filename, $loglevel, $buggling ));
            }

            self::$_instances[ $key ] = $logger;
            unset($logger);
        } catch (\Exception $e) {
            die(__METHOD__ . ' died at line ' .$e->getLine() . ' With exception ' . $e->getMessage() . ' code = ' . $e->getCode() );
        }
    }

    /**
     * Получает скоуп
     *
     * @param null $scope
     * @return Logger
     */
    public static function scope($scope = null):Logger
    {
        try {
            $key = self::getScopeKey( $scope );

            /*
            if (!self::checkInstance($key))
                throw new \Exception("AppLogger Class reports: given scope {$key} does not exists");
            */

            if (!self::checkInstance($key) and self::$_global_config['deferred_scope_creation']) {
                self::addDeferredScope($key);
            }

            return self::$_instances[ $key ];

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
        return self::$app_instance . ($scope ? (self::SCOPE_DELIMETER . (string)$scope) : '');
    }

}

# -eof-
