<?php

/*
 * Скорее всего сей механизм избыточен.
 *
 * Для перехвата немаскируемых исключений нужно использовать стандартный механизм либо надстройку над ним вида
 * ArrisException class extends \Exception, позволяющий передавать дополнительные данные
 *
 * А для перехвата маскируемых исключений этот зоопарк не нужен, если хочется стрелочек - достаточно создавать
 * AppStateHandler-экземпляр (только его надо объявить в отдельном файле, совпадающим с именем класса)
 *
 * ЛИБО иметь \functions\classes.php с декларацией вида
 *
 * if (!class_exists('Arris\AppStateHandler')):
 *    class AppStateHandler {...}
 * endif
 *
 */

namespace ArrisDeprecated;

class AppStateHandler {
    public $owner      = NULL;
    public $t_created  = NULL;
    public $t_modified = NULL;

    public $error       = FALSE;
    public $errorMsg    = '';
    public $errorCode   = -1;

    public function __construct($owner)
    {
        $this->owner = $owner;
        $this->t_created = microtime(true);
    }

    public function setState($is_error = true, $error_message = '', $error_code = -1)
    {
        $this->error = $is_error;
        $this->errorMsg = $error_message;
        $this->errorCode = $error_code;

        $this->t_modified = microtime(true);
    }
}

class AppState
{
    public static $_states = [];

    public static $_keys = [];

    public static function init()
    {
        //@do nothing now
    }

    /**
     * Добавляет состояние
     *
     * @param string $context
     * @return
     */
    public static function addState(string $context)
    {
        $key = self::getKey($context);

        $state = new AppStateHandler($key);

        self::$_states[ $key ] = $state;
        self::$_keys[] = $key;

        return $state;
    }

    public static function trace()
    {
        /**
         * @var AppStateHandler $state
         */
        foreach (self::$_states as $key => $state) {
            if ($state->error) {
                echo $key, PHP_EOL;
                echo "Last event at: ", $state->t_modified , PHP_EOL;
                echo "Code: ", $state->errorCode, PHP_EOL;
                echo "Message: ", $state->errorMsg, PHP_EOL;
                echo "-----------------------------------";
                echo PHP_EOL;
            }
        }
    }

    private static function getKey($context)
    {
        return md5($context . '|' . microtime(true));
    }
}