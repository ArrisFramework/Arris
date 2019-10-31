<?php /** @noinspection ALL */

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

use Monolog\Handler\NullHandler;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

interface AppLoggerConstants {
    const VERSION = '2.2';

    const APPLOGGER_ERROR_OPTIONS_EMPTY = 1;
    const APPLOGGER_ERROR_LOGFILENAME_EMPTY = 2;

    const SCOPE_DELIMETER = '.';

    const DEFAULT_LOG_FILENAME = '_.log';

    const DEFAULT_SCOPE_OPTIONS = [
        [ '100-debug.log',      Logger::DEBUG,      'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  \Monolog\Handler\StreamHandler::class],
        [ '200-info.log',       Logger::INFO,       'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  \Monolog\Handler\StreamHandler::class],
        [ '250-notice.log',     Logger::NOTICE,     'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  \Monolog\Handler\StreamHandler::class],
        [ '300-warning.log',    Logger::WARNING,    'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  \Monolog\Handler\StreamHandler::class],
        [ '400-error.log',      Logger::ERROR,      'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  \Monolog\Handler\StreamHandler::class],
        [ '500-critical.log',   Logger::CRITICAL,   'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  \Monolog\Handler\StreamHandler::class],
        [ '550-alert.log',      Logger::ALERT,      'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  \Monolog\Handler\StreamHandler::class],
        [ '600-emergency.log',  Logger::EMERGENCY,  'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  \Monolog\Handler\StreamHandler::class]
    ];

    /**
     * порядок опций в параметре $options метода addScope()
     */

    const addScope_OPTION_FILENAME = 0;
    const addScope_OPTION_LOGLEVEL = 1;
    const addScope_OPTION_BUBBLING = 'bubbling';
    const addScope_OPTION_ENABLE = 'enable';
    const addScope_OPTION_HANDLER = 'handler';
}

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

/**
 * Class AppLogger
 * @package Arris\Arris
 */
class AppLogger implements AppLoggerInterface, AppLoggerConstants
{
    private static $DEBUG = true;

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
    public static $_instances = [];

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
            = setOption($options, 'bubbling', null, false);

        // Уровень логгирования по умолчанию
        self::$_global_config['default_log_level']
            = setOption($options, 'default_log_level', null, Logger::DEBUG);

        // дефолтные значения для всего AppLogger
        self::$_global_config['default_logfile_path']
            = setOption($options, 'default_logfile_path', null, '');

        if (!empty(self::$_global_config['default_logfile_path'])) {
            self::$_global_config['default_logfile_path']
                = rtrim(self::$_global_config['default_logfile_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR ;
        }

        self::$_global_config['default_logfile_prefix']
            = setOption($options, 'default_logfile_prefix', null, '');

        self::$_global_config['default_log_file']
            = setOption($options, 'default_log_file', null, self::DEFAULT_LOG_FILENAME);

        self::$_global_config['default_handler']
            = setOption($options, 'handler', null, \Monolog\Handler\StreamHandler::class);

        // добавлять ли скоуп к имени логгера в файле лога
        self::$_global_config['add_scope_to_log']
            = setOption($options, 'add_scope_to_log', null, false);

        // опции Deferred-скоупов
        self::$_global_config['deferred_scope_creation']
            = setOption($options, 'deferred_scope_creation', null, true);

        self::$_global_config['deferred_scope_separate_files']
            = setOption($options, 'deferred_scope_separate_files', null, true);
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

            // if (self::$DEBUG) var_dump("addScope :: Adding logger {$logger_name}, scope `{$scope}`, internal key `{$internal_key}`");

            foreach ($scope_levels as $level) {
                $filename = self::createLoggerFilename($scope, $level, $is_deferred_scope); // нужно как-то сообщить, что это создается DeferredScope

                $loglevel = $level[ self::addScope_OPTION_LOGLEVEL ] ?? self::$_global_config['default_log_level'];

                $level_options = array(
                    'enable'    =>  setOption($level, 'enable', null, $scope_logging_enabled),
                    'bubbling'  =>  setOption($level, 'bubbling', null, self::$_global_config['bubbling']),
                    'handler'   =>  setOption($level, 'handler', null, \Monolog\Handler\StreamHandler::class)
                );

                // NullHandler если логгер так или иначе отключен
                if ($level_options['enable'] === false) {
                    $level_options['handler'] = \Monolog\Handler\NullHandler::class;
                }

                if ( $level_options['enable'] == false || $scope_logging_enabled == false ) {

                    // NULL Handler
                    $level_options['enable'] = false;
                    $logger->pushHandler( new \Monolog\Handler\NullHandler($loglevel) );

                } elseif ( $level_options['handler'] == \Monolog\Handler\StreamHandler::class || $level_options['handler'] === NULL ) {

                    // Stream Handler
                    $logger->pushHandler( new \Monolog\Handler\StreamHandler($filename, $loglevel, $level_options['bubbling']) );

                } elseif ( in_array('Monolog\Handler\HandlerInterface', class_implements($level_options['handler'])) ) {

                    // via HandlerInterface
                    $logger->pushHandler( $level_options['handler'] );

                } else {

                    // NULL Handler
                    $logger->pushHandler( new \Monolog\Handler\NullHandler($loglevel) );

                }

                self::$_configs[ $internal_key ][ $loglevel ] = $level_options;

            } //foreach
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
        // хотя...
        // $scope_key = self::getScopeKey($scope);
        // $is_deferred = array_key_exists($scope_key, self::$_configs) && array_key_exists($level[ self::addScope_OPTION_LOGLEVEL ], self::$_configs[ $scope_key ]);
        // нет, этот способ не работает, передавать придется прямо

        // вообще, проверим, пишется ли deferred-лог в разные файлы?
        $is_deferred = $is_deferred && self::$_global_config['deferred_scope_separate_files'];

        $file_prefix = $is_deferred ? $scope . self::SCOPE_DELIMETER : '';

        return $filepath . $file_prefix . $filename;
    }

}

# -eof-
