<?php
/**
 * User: Karel Wintersky
 *
 * Class AppLogger
 * Namespace: Arris
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


}

/**
 * Class AppLogger
 * @package Arris\Arris
 */
class AppLogger implements AppLoggerInterface
{
    const VERSION = '1.0';
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
        self::$app_instance = $app_instance_id;

        self::$_global_config['bubbling'] = $options['bubbling'] ?? false;
        self::$_global_config['default_log_level'] = $options['default_log_level'] ?? Logger::DEBUG;
    }

    /**
     * Добавляет скоуп
     *
     * @param $scope
     * @param $options
     * @throws \Exception
     */
    public static function addScope($scope, $options)
    {
        $key = self::getScopeKey($scope);

        $logger = new Logger($key);

        foreach ($options as $option) {
            $filename = $option[0];
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
     * @throws \Exception
     */
    public static function scope($scope = null)
    {
        $key = self::getScopeKey( $scope );

        if (!self::checkInstance($key))
            throw new \Exception("AppLogger Class reports: given scope {$key} does not exists");

        return self::$_instances[ $key ];

        // new self($scope)
        // return self::$_instances[ $key ];
    }

    /**
     * Проверяет существование инстанса логгера
     *
     * @param $key
     * @return bool
     */
    private static function checkInstance($key)
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