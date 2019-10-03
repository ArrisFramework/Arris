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

use Monolog\Handler\NullHandler;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

/**
 * Interface AppLoggerInterface
 * @package Arris\Arris
 */
interface AppLoggerInterface
{
    /**
     * порядок опций в параметре $options метода addScope()
     */
    const addScopeLegacy_OPTION_FILENAME = 0;
    const addScopeLegacy_OPTION_LOGLEVEL = 1;
    const addScopeLegacy_OPTION_BUBBLING = 2;
    const addScopeLegacy_OPTION_USELOGGER = 3;

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
     * @param $scope_levels - массив кортежей:
     * [ filename , logging_level, use_logger ], где:
     * - filename - имя файла лога
     * - уровень логгирования - уровень логгирования (переменные Logger::DEBUG etc)
     * - is_bubbling - позволять ли "всплывать" запросам на логгирование? (false - нет)
     * - use_logger - [true], булево значение, FALSE означает использовать для этого уровня логгирования NULL Handler
     *
     * @param bool $scope_enable_logging - false - использовать NullLogger для всего Scope
     */
    public static function addScope_legacy($scope, $scope_levels, $scope_enable_logging = true);

    /**
     * Добавляет скоуп
     *
     * @param null $scope - имя скоупа
     * @param array $scope_levels - массив кортежей:
     * [ filename , logging_level, options ], где:
     * - filename - имя файла лога
     * - logging_level - уровень логгирования - уровень логгирования (переменные Logger::DEBUG etc)
     * - options - хэш с возможными параметрами:
     * [
     *   - enabled - [TRUE], использовать ли логгер для этого уровня
     *   - bubbling - [FALSE], всплывает ли сообщение логгирования
     *   - handler - [НЕ ОПРЕЛЕЛЕНО] либо инстанс Хэндлера, реализующего интерфейс Monolog\Handler\HandlerInterface
     * ]
     * @param bool $scope_enabled_logging
     * @return mixed
     */
    public static function addScope($scope = null, $scope_levels = [], $scope_enabled_logging = true);

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

    /**
     * Добавляет null-logger
     * @return mixed Logger
     */
    public static function addNullLogger();
}

/**
 * Class AppLogger
 * @package Arris\Arris
 */
class AppLogger implements AppLoggerInterface
{
    const VERSION = '2.0';

    const APPLOGGER_ERROR_OPTIONS_EMPTY = 1;
    const APPLOGGER_ERROR_LOGFILENAME_EMPTY = 2;

    const SCOPE_DELIMETER = '.';

    const DEFAULT_LOG_FILENAME = '_.log';

    const DEFAULT_LEVEL_OPTIONS = [
        'bubbling'  =>  false,
        'enable'    =>  true,
        'handler'   =>  \Monolog\Handler\StreamHandler::class
    ];

    const DEFAULT_SCOPE_OPTIONS = [
        [ '100-debug.log',      Logger::DEBUG,      self::DEFAULT_LEVEL_OPTIONS],
        [ '200-info.log',       Logger::INFO,       self::DEFAULT_LEVEL_OPTIONS],
        [ '250-notice.log',     Logger::NOTICE,     self::DEFAULT_LEVEL_OPTIONS],
        [ '300-warning.log',    Logger::WARNING,    self::DEFAULT_LEVEL_OPTIONS],
        [ '400-error.log',      Logger::ERROR,      self::DEFAULT_LEVEL_OPTIONS],
        [ '500-critical.log',   Logger::CRITICAL,   self::DEFAULT_LEVEL_OPTIONS],
        [ '550-alert.log',      Logger::ALERT,      self::DEFAULT_LEVEL_OPTIONS],
        [ '600-emergency.log',  Logger::EMERGENCY,  self::DEFAULT_LEVEL_OPTIONS]
    ];

    const DEFAULT_SCOPE_OPTIONS_LEGACY = [
        [ '100-debug.log',      Logger::DEBUG,      FALSE],
        [ '200-info.log',       Logger::INFO,       FALSE],
        [ '250-notice.log',     Logger::NOTICE,     FALSE],
        [ '300-warning.log',    Logger::WARNING,    FALSE],
        [ '400-error.log',      Logger::ERROR,      FALSE],
        [ '500-critical.log',   Logger::CRITICAL,   FALSE],
        [ '550-alert.log',      Logger::ALERT,      FALSE],
        [ '600-emergency.log',  Logger::EMERGENCY,  FALSE]
    ];

    /**
     * @var array
     */
    public static $_global_config = [];

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
        self::$application = $application;

        self::$instance = $instance;

        self::$_global_config['bubbling'] = setOption($options, 'bubbling', null, false);
        self::$_global_config['default_log_level'] = setOption($options, 'default_log_level', null, Logger::DEBUG);
        self::$_global_config['add_scope_to_log'] = setOption($options, 'add_scope_to_log', null, false);
        self::$_global_config['default_logfile_path'] = setOption($options, 'default_logfile_path', null, '');

        if (!empty(self::$_global_config['default_logfile_path'])) {
            self::$_global_config['default_logfile_path'] = rtrim(self::$_global_config['default_logfile_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR ;
        }

        self::$_global_config['default_logfile_prefix'] = setOption($options, 'default_logfile_prefix', null, '');

        self::$_global_config['default_log_file'] = setOption($options, 'default_log_file', null, self::DEFAULT_LOG_FILENAME);

        self::$_global_config['deferred_scope_creation'] = setOption($options, 'deferred_scope_creation', null, true);
    }

    public static function addScope($scope = null, $scope_levels = [], $scope_enabled_logging = true)
    {
        if (empty($scope_levels)) {
            $scope_levels = self::DEFAULT_SCOPE_OPTIONS;
        }

        try {
            $logger_name = self::getLoggerName($scope);
            $internal_key = self::getScopeKey($scope);

            $logger = new Logger($logger_name);

            foreach ($scope_levels as $level) {
                $filename
                    = empty($level[ self::addScopeLegacy_OPTION_FILENAME ])
                    ? self::$_global_config['default_log_file']
                    : $level[ self::addScopeLegacy_OPTION_FILENAME ];
                $filename
                    = self::$_global_config['default_logfile_path']
                    . self::$_global_config['default_logfile_prefix']
                    . $filename;

                $loglevel = $level[ self::addScopeLegacy_OPTION_LOGLEVEL ] ?? self::$_global_config['default_log_level'];

                //@todo: оптимизировать блок
                // если массива опций уровня логгирования (3 аргумент) пуст или это не массив
                if (empty($level[ 2 ]) || !is_array($level[ 2 ])) {
                    $level_options = [
                        'bubbling'  =>  self::$_global_config['bubbling'],
                        'enable'    =>  $scope_enabled_logging,
                        'handler'   =>  \Monolog\Handler\StreamHandler::class
                    ];
                } else {
                    // это массив с какими-то опциями
                    $level_options = [
                        'bubbling'  =>  setOption($level[2], 'bubbling', null, self::$_global_config['bubbling']),
                        'enable'    =>  setOption($level[2], 'enabled', null, $scope_enabled_logging),
                        'handler'   =>  setOption($level[2],'handler',null,\Monolog\Handler\StreamHandler::class)
                    ];
                    // если уровень запрещен - ставим null-logger
                    if ($level_options['enable'] == false) {
                        $level_options['handler'] = \Monolog\Handler\NullHandler::class;
                    }
                }
                // проверка, что хэндлер реализует интерфейс:
                var_dump( in_array('Monolog\Handler\HandlerInterface', class_implements($level_options['handler'])) );

                if ($level_options['enable'] == false || $scope_enabled_logging == false) {
                    // логгирование запрещено
                    $logger->pushHandler(new \Monolog\Handler\NullHandler($loglevel));

                } elseif ($level_options['handler'] == \Monolog\Handler\StreamHandler::class) {
                    //хэндлер SteamHandler
                    $logger->pushHandler(new \Monolog\Handler\StreamHandler($filename, $loglevel, $level_options['bubbling'] ));

                } elseif ( in_array('Monolog\Handler\HandlerInterface', class_implements($level_options['handler'])) ) {
                    // хэндлер реализует интерфейс
                    $logger->pushHandler( $level_options['handler'] );

                } else {
                    // во всех остальных случаях NULL
                    $logger->pushHandler(new \Monolog\Handler\NullHandler($loglevel));

                }

            } //foreach
            self::$_instances[ $internal_key ] = $logger;
            unset($logger);

        } catch (\Exception $e) {
            die(__METHOD__ . ' died at line ' .$e->getLine() . ' With exception ' . $e->getMessage() . ' code = ' . $e->getCode() );
        }

    }

    public static function addScope_legacy($scope = null, $scope_levels = [], $scope_enable_logging = true)
    {
        if (empty($scope_levels)) {
            $scope_levels = self::DEFAULT_SCOPE_OPTIONS_LEGACY;
        }

        try {
            $logger_name = self::getLoggerName($scope);
            $internal_key = self::getScopeKey($scope);

            $logger = new Logger($logger_name);

            foreach ($scope_levels as $option) {

                $filename
                    = empty($option[ self::addScopeLegacy_OPTION_FILENAME ])
                    ? self::$_global_config['default_log_file']
                    : $option[ self::addScopeLegacy_OPTION_FILENAME ];

                $filename
                    = self::$_global_config['default_logfile_path']
                    . self::$_global_config['default_logfile_prefix']
                    . $filename;

                $loglevel = $option[ self::addScopeLegacy_OPTION_LOGLEVEL ] ?? self::$_global_config['default_log_level'];
                $bubbling = $option[ self::addScopeLegacy_OPTION_BUBBLING ] ?? self::$_global_config['bubbling'];

                //@todo: more handlers, FALSE is null handler

                // параметр use_logger перекрывает частные опции логгирования
                if ($scope_enable_logging) {
                    // если уровень логгирования прямо запрещен (или не указан) - делаем нулл-логгер
                    if (array_key_exists(self::addScopeLegacy_OPTION_USELOGGER, $option) && $option[ self::addScopeLegacy_OPTION_USELOGGER ] == FALSE) {
                        $logger->pushHandler(new \Monolog\Handler\NullHandler($loglevel));
                    } else {
                        // иначе stream logger
                        $logger->pushHandler(new StreamHandler($filename, $loglevel, $bubbling ));
                    }
                } else {
                    //
                    $logger->pushHandler(new \Monolog\Handler\NullHandler());
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
        $options = self::DEFAULT_SCOPE_OPTIONS_LEGACY;

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
