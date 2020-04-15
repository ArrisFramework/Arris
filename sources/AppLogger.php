<?php

/**
 * User: Karel Wintersky
 *
 * Class AppLogger
 * Namespace: Arris
 *
 * Library: https://github.com/KarelWintersky/Arris
 *
 * Date: 31.10.2019 14:00:00
 *
 */

namespace Arris;

use Exception;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\NullHandler;

/**
 * Class AppLogger
 * @package Arris\Arris
 */
class AppLogger implements AppLoggerInterface, AppLoggerConstants
{
    /**
     * @var array $_instances \Monolog
     */
    public static $_instances = [];

    /**
     * @var array
     */
    public static $_global_config = [
        'bubbling'                      =>  false,
        'default_logfile_path'          =>  '',
        'default_log_level'             =>  Logger::DEBUG,
        'default_logfile_prefix'        =>  '',
        'default_log_file'              =>  self::DEFAULT_LOG_FILENAME,
        'default_handler'               =>  StreamHandler::class,
        'add_scope_to_log'              =>  false,
        'deferred_scope_creation'       =>  true,
        'deferred_scope_separate_files' =>  true
    ];

    /**
     * @var string
     */
    private static $application;

    /**
     * @var string
     */
    private static $instance;

    /**
     * @var array
     */
    private static $_configs = [];

    public static function init($application, $instance, $options = [])
    {
        self::$application = $application;
        self::$instance = $instance;

        // Всплывание лога
        self::$_global_config['bubbling']
            = setOption($options, 'bubbling', false);

        // Уровень логгирования по умолчанию
        self::$_global_config['default_log_level']
            = setOption($options, 'default_log_level', Logger::DEBUG);

        // дефолтные значения для всего AppLogger
        self::$_global_config['default_logfile_path']
            = setOption($options, 'default_logfile_path', '');

        if (!empty(self::$_global_config['default_logfile_path'])) {
            self::$_global_config['default_logfile_path']
                = rtrim(self::$_global_config['default_logfile_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR ;
        }

        self::$_global_config['default_logfile_prefix']
            = setOption($options, 'default_logfile_prefix', '');

        self::$_global_config['default_log_file']
            = setOption($options, 'default_log_file', self::DEFAULT_LOG_FILENAME);

        self::$_global_config['default_handler']
            = setOption($options, 'handler', StreamHandler::class);

        // добавлять ли скоуп к имени логгера в файле лога
        self::$_global_config['add_scope_to_log']
            = setOption($options, 'add_scope_to_log', false);

        // опции Deferred-скоупов
        self::$_global_config['deferred_scope_creation']
            = setOption($options, 'deferred_scope_creation', true);

        self::$_global_config['deferred_scope_separate_files']
            = setOption($options, 'deferred_scope_separate_files', true);
    }

    public static function addScope($scope = null, $scope_levels = [], $scope_logging_enabled = true, $is_deferred_scope = false)
    {
        if (empty($scope_levels)) {
            $scope_levels = self::DEFAULT_SCOPE_OPTIONS;
            $is_deferred_scope = true;
        }

        try {
            $logger_name = self::getLoggerName($scope);
            $internal_key = self::getScopeKey($scope);

            $logger = new Logger($logger_name);

            foreach ($scope_levels as $level) {
                $filename = self::createLoggerFilename($scope, $level, $is_deferred_scope);

                $loglevel = $level[ self::addScope_OPTION_LOGLEVEL ] ?? self::$_global_config['default_log_level'];

                $level_options = array(
                    'enable'    =>  setOption($level, 'enable', $scope_logging_enabled),
                    'bubbling'  =>  setOption($level, 'bubbling', self::$_global_config['bubbling']),
                    'handler'   =>  setOption($level, 'handler', StreamHandler::class)
                );

                // NullHandler если логгер так или иначе отключен
                if ($level_options['enable'] === false) {
                    $level_options['handler'] = \Monolog\Handler\NullHandler::class;
                }

                if ( $level_options['enable'] == false || $scope_logging_enabled == false ) {

                    // NULL Handler
                    $level_options['enable'] = false;
                    $logger->pushHandler( new \Monolog\Handler\NullHandler($loglevel) );

                } elseif ( $level_options['handler'] == StreamHandler::class || $level_options['handler'] === NULL ) {

                    // Stream Handler
                    $logger->pushHandler( new StreamHandler($filename, $loglevel, $level_options['bubbling']) );

                } elseif ( in_array('Monolog\Handler\HandlerInterface', class_implements($level_options['handler'])) ) {

                    // via HandlerInterface
                    $logger->pushHandler( /** @param \Monolog\Handler\HandlerInterface */ $level_options['handler'] );

                } else {

                    // NULL Handler
                    $logger->pushHandler( new \Monolog\Handler\NullHandler($loglevel) );

                }

                self::$_configs[ $internal_key ][ $loglevel ] = $level_options;

            } //foreach
            self::$_instances[ $internal_key ] = $logger;
            unset($logger);

        } catch (Exception $e) {
            die(__METHOD__ . ' died at line ' .$e->getLine() . ' With exception ' . $e->getMessage() . ' code = ' . $e->getCode() );
        }
    }

    public static function addNullLogger()
    {
        return (new Logger('null'))->pushHandler(new \Monolog\Handler\NullHandler());
    }

    public static function scope($scope = null):Logger
    {
        try {
            $internal_key = self::getScopeKey( $scope );

            if (!self::checkInstance($internal_key) and self::$_global_config['deferred_scope_creation']) {
                self::addDeferredScope($scope);
            }

            return self::$_instances[ $internal_key ];

        } catch (Exception $e) {
            die(__METHOD__ . ' died at line ' .$e->getLine() . ' With exception ' . $e->getMessage() . ' code = ' . $e->getCode() );
        }
    }

    public static function getAppLoggerConfig()
    {
        return self::$_global_config;
    }

    /**
     * Возвращает опции логгер-скоупа
     * @param null $scope
     * @return mixed
     */
    public static function getLoggerConfig($scope = null)
    {
        return self::$_configs[ self::getScopeKey( $scope ) ];
    }

    /**
     * Поздняя инициализация скоупа со значениями по умолчанию.
     *
     * @param null $scope
     */
    private static function addDeferredScope($scope = null)
    {
        self::addScope($scope, self::DEFAULT_SCOPE_OPTIONS, true, true);
    }

    /**
     * Проверяет существование инстанса логгера ПО internal_key (!!!), не по имени скоупа!
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
        $scope = $scope ? (self::SCOPE_DELIMETER . (string)$scope) : '';
        return self::$application . self::$instance . $scope;
    }

    /**
     * Проверяет существование скоупа по имени
     *
     * @param null $scope
     * @return bool
     */
    private static function checkScopeExist($scope = null)
    {
        $key = self::getScopeKey($scope);
        return self::checkInstance($key);
    }

    /**
     * Генерирует имя логгера
     *
     * @param null $scope
     * @return string
     */
    private static function getLoggerName($scope = null)
    {
        $scope = (string)$scope;

        return
            self::$_global_config['add_scope_to_log']
                ? self::$application . self::SCOPE_DELIMETER . self::$instance . self::SCOPE_DELIMETER . $scope
                : self::$application . self::SCOPE_DELIMETER . self::$instance;
    }

    /**
     * Генерирует имя файла для логгера/скоупа
     *
     * @param $scope
     * @param $level
     * @return string
     */
    private static function createLoggerFilename($scope, $level, $is_deferred = false)
    {
        $filename
            = empty($level[ self::addScope_OPTION_FILENAME ])
            ? self::$_global_config['default_log_file']
            : $level[ self::addScope_OPTION_FILENAME ];

        $filepath = self::$_global_config['default_logfile_path'] . self::$_global_config['default_logfile_prefix'];

        // если мы генерим имя файла для DeferredScope - префикс имени файла = scope, иначе ''
        // определить это мы вызовом метода не можем, придется передавать параметром

        // вообще, проверим, пишется ли deferred-лог в разные файлы?
        $is_deferred = $is_deferred && self::$_global_config['deferred_scope_separate_files'];

        $file_prefix = $is_deferred ? $scope . self::SCOPE_DELIMETER : '';

        return $filepath . $file_prefix . $filename;
    }

}

# -eof-
