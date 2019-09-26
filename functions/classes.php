<?php

namespace Arris;

if (!class_exists('Arris\AppStateHandler')) {
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
}