<?php

namespace Arris;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

interface AppLoggerConstants {
    const VERSION = '2.2';

    const APPLOGGER_ERROR_OPTIONS_EMPTY = 1;
    const APPLOGGER_ERROR_LOGFILENAME_EMPTY = 2;

    const SCOPE_DELIMETER = '.';

    const DEFAULT_LOG_FILENAME = '_.log';
    
    /**
     * Detailed debug information
     */
    const DEBUG = 100;
    
    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const INFO = 200;
    
    /**
     * Uncommon events
     */
    const NOTICE = 250;
    
    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const WARNING = 300;
    
    /**
     * Runtime errors
     */
    const ERROR = 400;
    
    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const CRITICAL = 500;
    
    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const ALERT = 550;
    
    /**
     * Urgent alert.
     */
    const EMERGENCY = 600;
    
    /**
     * Monolog API version
     *
     * This is only bumped when API breaks are done and should
     * follow the major version of the library
     *
     * @var int
     */
    const API = 1;
    
    
    const DEFAULT_SCOPE_OPTIONS = [
        [ '100-debug.log',      self::DEBUG,      'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '200-info.log',       self::INFO,       'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '250-notice.log',     self::NOTICE,     'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '300-warning.log',    self::WARNING,    'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '400-error.log',      self::ERROR,      'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '500-critical.log',   self::CRITICAL,   'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '550-alert.log',      self::ALERT,      'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '600-emergency.log',  self::EMERGENCY,  'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class]
    ];

    /**
     * Порядок опций в параметре $options метода addScope()
     */
    const addScope_OPTION_FILENAME = 0;
    const addScope_OPTION_LOGLEVEL = 1;
    const addScope_OPTION_OPTIONS = 2;

    const addScope_OPTION_BUBBLING = 'bubbling';
    const addScope_OPTION_ENABLE = 'enable';
    const addScope_OPTION_HANDLER = 'handler';
}