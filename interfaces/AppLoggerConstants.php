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

    const DEFAULT_SCOPE_OPTIONS = [
        [ '100-debug.log',      Logger::DEBUG,      'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '200-info.log',       Logger::INFO,       'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '250-notice.log',     Logger::NOTICE,     'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '300-warning.log',    Logger::WARNING,    'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '400-error.log',      Logger::ERROR,      'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '500-critical.log',   Logger::CRITICAL,   'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '550-alert.log',      Logger::ALERT,      'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class],
        [ '600-emergency.log',  Logger::EMERGENCY,  'bubbling'  =>  false, 'enable'    =>  true, 'handler'   =>  StreamHandler::class]
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